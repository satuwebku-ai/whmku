<?php

namespace App\Services\Provisioning;

use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\Invoice;
use App\Models\Order;
use App\Notifications\OrderProvisioned;
use App\Services\Domain\DomainRegistrarFactory;
use App\Services\Hosting\HostingPanelFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Dipanggil otomatis lewat event Invoice::updated() saat status invoice
 * berubah jadi "paid" (lihat App\Models\Invoice) — baik itu dari webhook
 * gateway, approve transfer manual, atau admin edit langsung. Semua jalur
 * pelunasan berujung ke sini.
 *
 * Untuk tiap order di invoice: hosting → buat akun cPanel via Fase 3,
 * domain → registrasi via Fase 4. Kegagalan satu item TIDAK menghentikan
 * item lain — dicatat di provision_message masing-masing supaya admin
 * bisa menindaklanjuti manual.
 */
class ProvisioningService
{
    public function provisionInvoice(Invoice $invoice): void
    {
        $orders = $this->resolveOrders($invoice);

        if ($orders->isEmpty()) {
            return;
        }

        $hostingCredentials = [];
        $domainResults = [];

        foreach ($orders as $order) {
            try {
                if ($order->order_type === 'hosting' && $order->hostingAccount) {
                    $cred = $this->provisionHosting($order->hostingAccount, $order);
                    if ($cred) {
                        $hostingCredentials[] = $cred;
                    }
                } elseif ($order->order_type === 'domain' && $order->domain) {
                    $result = $this->provisionDomain($order->domain, $order);
                    if ($result) {
                        $domainResults[] = $result;
                    }
                }
            } catch (Throwable $e) {
                Log::error("Provisioning order #{$order->id} gagal: " . $e->getMessage(), ['order_id' => $order->id]);
            }

            // Order dianggap selesai dari sisi billing begitu invoice lunas,
            // terlepas dari sukses/gagalnya provisioning otomatis — status
            // provisioning detail ada di provision_status hosting/domain-nya.
            if ($order->status === 'pending') {
                $order->update(['status' => 'active']);
            }
        }

        if ($hostingCredentials || $domainResults) {
            $this->notifyClient($invoice, $hostingCredentials, $domainResults);
        }
    }

    /**
     * Order dari invoice_items (checkout Fase 7c) kalau ada, atau fallback
     * ke relasi order() tunggal untuk invoice manual lama (Fase 2).
     */
    private function resolveOrders(Invoice $invoice): \Illuminate\Support\Collection
    {
        $invoice->loadMissing(['items.order.hostingAccount.serverModel', 'items.order.domain.registrar', 'order']);

        if ($invoice->items->isNotEmpty()) {
            return $invoice->items->pluck('order')->filter()->unique('id')->values();
        }

        return collect([$invoice->order])->filter()->values();
    }

    /**
     * @return array{domain: string, username: string, password: string}|null
     */
    private function provisionHosting(HostingAccount $account, Order $order): ?array
    {
        // Sudah pernah diprovision (mis. event ini terpicu lagi) — jangan
        // buat akun dobel di server.
        if ($account->provision_status === 'provisioned') {
            return null;
        }

        // Tidak ada server tujuan = memang manual, bukan kegagalan.
        if (! $account->server_id) {
            return null;
        }

        $server = $account->serverModel;

        if (! $server) {
            $account->update(['provision_status' => 'failed', 'provision_message' => 'Server tujuan tidak ditemukan.']);

            return null;
        }

        $username = $account->username ?: $this->generateUsername($account->domain);
        $password = $this->generatePassword();

        $result = HostingPanelFactory::make($server)->createAccount([
            'domain'   => $account->domain,
            'username' => $username,
            'password' => $password,
            'package'  => $account->package,
            'email'    => $order->client->email ?? '',
        ]);

        $account->update([
            'username'          => $username,
            'status'            => $result['success'] ? 'active' : $account->status,
            'provision_status'  => $result['success'] ? 'provisioned' : 'failed',
            'provision_message' => $result['message'],
        ]);

        if (! $result['success']) {
            return null;
        }

        return ['domain' => $account->domain, 'username' => $username, 'password' => $password];
    }

    /**
     * @return array{domain: string, success: bool, message: string}|null
     */
    private function provisionDomain(Domain $domain, Order $order): ?array
    {
        if ($domain->provision_status === 'registered') {
            return null;
        }

        // TLD tanpa registrar (mis. TLD demo ".test" dari lumora:demo-tld,
        // atau domain yang sengaja diproses manual) — TIDAK ada API untuk
        // dipanggil, tapi domainnya tetap harus ditandai aktif di database.
        //
        // Sebelumnya di sini langsung `return null` tanpa menyentuh status
        // domain sama sekali. Order tetap ditandai "Active" oleh
        // provisionInvoice() (baris terpisah, tidak bergantung ke sini),
        // sehingga admin melihat order aktif padahal Domain::status masih
        // "pending" — itu sebabnya dashboard klien menghitung 0 domain
        // aktif meski order-nya sudah "Active".
        if (! $domain->registrar_id) {
            $domain->update([
                'status' => 'active',
                'register_date' => $domain->register_date ?: now(),
                'expiry_date' => $domain->expiry_date ?: now()->addYears(max($domain->years ?: 1, 1)),
                'provision_status' => 'manual',
                'provision_message' => 'Domain tanpa registrar — ditandai aktif secara manual, tidak ada pendaftaran API yang dilakukan.',
            ]);

            return null;
        }

        $registrar = $domain->registrar;
        $client = $order->client;

        if (! $registrar || ! $client) {
            $domain->update(['provision_status' => 'failed', 'provision_message' => 'Registrar atau data klien tidak ditemukan.']);

            return ['domain' => $domain->domain_name, 'success' => false, 'message' => 'Registrar atau data klien tidak ditemukan.'];
        }

        // WHOIS butuh alamat lengkap — kalau klien belum mengisi provinsi/
        // kode pos (Fase 7a hanya mewajibkan kota & negara), jangan kirim
        // data setengah ke registrar. Lebih baik gagal jelas daripada
        // registrasi domain dengan alamat palsu/kosong.
        if (blank($client->address) || blank($client->city) || blank($client->state) || blank($client->postal_code) || blank($client->phone)) {
            $message = 'Data alamat klien belum lengkap untuk registrasi WHOIS (alamat/kota/provinsi/kode pos/telepon). Lengkapi di halaman Profil, lalu proses manual.';
            $domain->update(['provision_status' => 'failed', 'provision_message' => $message]);

            return ['domain' => $domain->domain_name, 'success' => false, 'message' => $message];
        }

        [$firstName, $lastName] = $this->splitName($client->name);

        $result = DomainRegistrarFactory::make($registrar)->registerDomain([
            'domain' => $domain->domain_name,
            'years'  => $domain->years ?: 1,
            // Diteruskan ke registrar saat registrasi — LiquidService sudah
            // bisa membaca ini sejak awal, hanya belum pernah ada yang
            // benar-benar mengisinya dari alur checkout sampai sekarang.
            'whois_privacy' => (bool) $domain->whois_privacy,
            'contact' => [
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'address'      => $client->address,
                'city'         => $client->city,
                'state'        => $client->state,
                'postal_code'  => $client->postal_code,
                'country'      => $this->countryCode($client->country),
                'phone'        => $client->phone,
                'email'        => $client->email,
            ],
        ]);

        $domain->update([
            'provision_status'  => $result['success'] ? 'registered' : 'failed',
            'provision_message' => $result['message'],
            'status'            => $result['success'] ? 'active' : $domain->status,
            'register_date'     => $result['success'] ? now() : $domain->register_date,
            'expiry_date'       => $result['success'] ? now()->addYears($domain->years ?: 1) : $domain->expiry_date,
        ]);

        return ['domain' => $domain->domain_name, 'success' => $result['success'], 'message' => $result['message']];
    }

    private function notifyClient(Invoice $invoice, array $hostingCredentials, array $domainResults): void
    {
        $client = $invoice->client;

        if (! $client) {
            return;
        }

        try {
            $client->notify(new OrderProvisioned($hostingCredentials, $domainResults));
        } catch (Throwable $e) {
            // Sama seperti OTP (Fase 6a): email bisa gagal kalau SMTP belum
            // dikonfigurasi. Kredensial tetap TIDAK disimpan di database
            // (hanya sekali dikirim), jadi kalau email gagal, admin perlu
            // reset password akun cPanel manual dan infokan ke klien.
            Log::error('Gagal mengirim email kredensial provisioning: ' . $e->getMessage(), ['invoice_id' => $invoice->id]);
        }
    }

    /**
     * Username cPanel: alfanumerik, diawali huruf, maks 8 karakter, unik.
     */
    private function generateUsername(string $domain): string
    {
        $sld = (string) Str::of($domain)->before('.')->lower()->replaceMatches('/[^a-z0-9]/', '');

        if ($sld === '' || ctype_digit($sld[0])) {
            $sld = 'u' . $sld;
        }

        $base = substr($sld, 0, 8) ?: 'ulumora';
        $username = $base;
        $i = 1;

        while (HostingAccount::where('username', $username)->exists()) {
            $suffix = (string) $i++;
            $username = substr($base, 0, 8 - strlen($suffix)) . $suffix;
        }

        return $username;
    }

    private function generatePassword(): string
    {
        return Str::password(14, symbols: false) . 'Aa1!';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = explode(' ', trim($name), 2);

        return [$parts[0], $parts[1] ?? $parts[0]];
    }

    /**
     * Peta sederhana nama negara -> kode ISO alpha-2 yang dipakai registrar.
     * Client menyimpan nama negara bebas teks (Fase 7a), jadi ini tebakan
     * terbaik untuk kasus umum. Kalau tidak dikenali, default ke ID.
     */
    private function countryCode(?string $country): string
    {
        if (! $country) {
            return 'ID';
        }

        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        $map = [
            'indonesia' => 'ID', 'malaysia' => 'MY', 'singapore' => 'SG', 'singapura' => 'SG',
            'united states' => 'US', 'usa' => 'US', 'amerika serikat' => 'US',
            'united kingdom' => 'GB', 'uk' => 'GB', 'inggris' => 'GB',
            'australia' => 'AU', 'thailand' => 'TH', 'vietnam' => 'VN',
            'philippines' => 'PH', 'filipina' => 'PH', 'india' => 'IN', 'japan' => 'JP', 'jepang' => 'JP',
        ];

        return $map[strtolower(trim($country))] ?? 'ID';
    }

    /**
     * Dipanggil dari hook "invoice lunas" yang sama dengan provisioning
     * order baru — tapi ini untuk kasus yang berbeda: invoice PERPANJANGAN
     * layanan yang sudah aktif, dibuat oleh lumora:generate-renewal-invoices.
     *
     * Tidak ada apa pun yang perlu di-"provision" ulang (akun hosting dan
     * domainnya sudah ada) — yang perlu dilakukan hanya menggeser tanggal
     * jatuh tempo/kedaluwarsa ke siklus berikutnya, dan melepas tanda
     * "invoice perpanjangan sedang menunggu" supaya siklus berikutnya bisa
     * dibuatkan invoice baru lagi nanti.
     */
    public function processRenewalPayment(Invoice $invoice): void
    {
        $hosting = HostingAccount::where('renewal_invoice_id', $invoice->id)->first();

        if ($hosting) {
            $wasSuspended = $hosting->status === 'suspended';

            // Kalau akun sungguhan pernah disuspend lewat API (auto-suspend
            // atau manual), harus dinyalakan lagi lewat API juga — mengubah
            // status di database saja TIDAK membuka akses klien di server
            // yang sebenarnya, akun tetap terkunci di sisi cPanel/WHM.
            if ($wasSuspended && $hosting->serverModel && $hosting->username) {
                try {
                    $result = HostingPanelFactory::make($hosting->serverModel)->unsuspendAccount($hosting->username);

                    if (! $result['success']) {
                        Log::error('Gagal unsuspend otomatis setelah pembayaran: ' . $result['message'], [
                            'hosting_account_id' => $hosting->id,
                        ]);
                    }
                } catch (Throwable $e) {
                    Log::error('Unsuspend otomatis error: ' . $e->getMessage(), ['hosting_account_id' => $hosting->id]);
                }
            }

            $hosting->update([
                'next_due_date' => $hosting->nextCycleDate(),
                'renewal_invoice_id' => null,
                'status' => $wasSuspended ? 'active' : $hosting->status,
            ]);

            ActivityLog::record(
                'service',
                $wasSuspended ? 'Hosting diaktifkan kembali: ' . $hosting->domain : 'Hosting diperpanjang: ' . $hosting->domain,
                'Jatuh tempo baru: ' . $hosting->next_due_date->format('d M Y'),
                route('admin.hosting-accounts.details', $hosting),
                'success',
                $hosting->client_id,
            );
        }

        $domain = Domain::where('renewal_invoice_id', $invoice->id)->first();

        if ($domain) {
            // Tahun ditambahkan dari expiry_date SEBELUMNYA, bukan dari hari
            // ini — supaya domain yang dibayar lebih awal tidak kehilangan
            // sisa masa aktifnya.
            $newExpiry = ($domain->expiry_date ?: now())->copy()->addYear();

            $domain->update([
                'expiry_date' => $newExpiry,
                'renewal_invoice_id' => null,
                'status' => 'active',
            ]);

            // Domain dengan registrar sungguhan idealnya juga memanggil API
            // renew di sini. Belum diimplementasikan — untuk sekarang
            // perpanjangan dicatat di database, dan admin perlu memastikan
            // perpanjangan juga diproses di sisi registrar kalau TLD-nya
            // bukan TLD demo. Ditandai jelas di provision_message supaya
            // tidak diam-diam terlewat.
            if ($domain->registrar_id) {
                $domain->update([
                    'provision_message' => 'Perpanjangan tercatat di sistem pada ' . now()->format('d M Y')
                        . '. Pastikan juga diperpanjang di panel registrar jika belum otomatis.',
                ]);
            }

            ActivityLog::record(
                'domain',
                'Domain diperpanjang: ' . $domain->domain_name,
                'Kedaluwarsa baru: ' . $domain->expiry_date->format('d M Y'),
                route('admin.domains.details', $domain),
                'success',
                $domain->client_id,
            );
        }
    }
}
