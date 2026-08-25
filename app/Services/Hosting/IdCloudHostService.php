<?php

namespace App\Services\Hosting;

use App\Models\Server;
use App\Services\Hosting\Contracts\HostingPanelInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Integrasi IDCloudHost -- BEDA dari cPanel/DirectAdmin/Plesk: bukan
 * "panel" di server yang sudah ada, tapi menyediakan Virtual
 * Machine/VPS BARU sepenuhnya lewat API on-demand (create/delete VM
 * itu sendiri, bukan sekadar akun di dalam server).
 *
 * Field tabel servers dipakai ulang secara kreatif (tanpa kolom baru):
 *   - hostname     -> slug lokasi datacenter (jkt01/jkt02/jkt03/sgp01),
 *                     kosongkan untuk pakai lokasi default akun.
 *   - api_username -> billing_account_id (opsional -- boleh kosong
 *                     kalau API token sudah dibatasi ke satu akun billing)
 *   - api_token    -> API key dari dashboard IDCloudHost
 *
 * Field "package" (dari HostingAccount/Product->panel_package) berisi
 * JSON, BUKAN nama paket seperti WHM -- karena IDCloudHost tidak
 * punya konsep "paket" baku, tiap VM ditentukan vcpu/ram/disk/OS
 * masing-masing secara eksplisit. Contoh isi panel_package:
 *   {"vcpu":2,"ram":2048,"disk":40,"os_name":"ubuntu","os_version":"22.04"}
 *
 * Dokumentasi API: https://api.idcloudhost.com/
 */
class IdCloudHostService implements HostingPanelInterface
{
    public function __construct(protected Server $server)
    {
    }

    /**
     * Semua endpoint resource (VM, billing, IP) itu location-specific --
     * slug lokasi disisipkan tepat setelah nomor versi. Dikosongkan
     * untuk memakai lokasi default akun IDCloudHost.
     */
    protected function baseUrl(): string
    {
        $slug = trim((string) $this->server->hostname);

        return $slug !== ''
            ? "https://api.idcloudhost.com/v1/{$slug}"
            : 'https://api.idcloudhost.com/v1';
    }

    /**
     * Field ini dipakai ulang lintas jenis panel -- kalau isinya bukan
     * angka murni (misal sisa "root" dari server cPanel lama, atau
     * salah ketik), jangan dikirim ke API IDCloudHost sebagai
     * billing_account_id -- itu akan selalu ditolak (400 "Invalid
     * whole number"). Anggap saja kosong dan biarkan fallback ke akun
     * billing default.
     */
    protected function billingAccountId(): ?string
    {
        $id = trim((string) $this->server->api_username);

        return ($id !== '' && ctype_digit($id)) ? $id : null;
    }

    /**
     * Resolve billing_account_id yang valid -- pakai yang di-set
     * eksplisit di kolom API Username kalau ada & berupa angka murni,
     * kalau tidak fallback ke akun billing default milik API key ini.
     * Dipakai bareng oleh getBillingAccount(), listFloatingIps(), dan
     * getUsage() supaya logic resolve-nya tidak dobel di 3 tempat.
     */
    protected function resolveBillingAccountId(): ?string
    {
        $id = $this->billingAccountId();

        if ($id !== null) {
            return $id;
        }

        $listResult = $this->call('get', '/payment/billing_account/list', [], 'https://api.idcloudhost.com/v1');

        if (! $listResult['success']) {
            return null;
        }

        $default = collect($listResult['raw'])->firstWhere('is_default', true)
            ?? ($listResult['raw'][0] ?? null);

        return $default['id'] ?? null;
    }

    /**
     * Uraikan panel_package (JSON) jadi array spesifikasi VM, dengan
     * nilai default yang aman kalau ada field yang tidak diisi.
     */
    protected function decodePackage(string $package): array
    {
        $spec = json_decode($package, true) ?: [];

        return [
            'vcpu'           => (int) ($spec['vcpu'] ?? 1),
            'ram'            => (int) ($spec['ram'] ?? 1024),
            'disk'           => (int) ($spec['disk'] ?? 20),
            'os_name'        => $spec['os_name'] ?? 'ubuntu',
            'os_version'     => $spec['os_version'] ?? '22.04',
            // Dipakai kartu harga per komponen (ChargeHourlyUsage) --
            // bukan cuma untuk provisioning API, tapi juga dasar
            // perhitungan tagihan per jam.
            'backup_enabled' => (bool) ($spec['backup_enabled'] ?? false),
            'snapshot_gb'    => (float) ($spec['snapshot_gb'] ?? 0),
        ];
    }

    /**
     * $baseUrlOverride dipakai untuk endpoint yang BUKAN location-
     * specific (billing, pricing, config) -- kalau tidak diisi, pakai
     * baseUrl() yang otomatis nempelin slug lokasi server ini.
     */
    protected function call(string $method, string $path, array $data = [], ?string $baseUrlOverride = null): array
    {
        try {
            $client = Http::withHeaders(['apikey' => $this->server->api_token])
                ->baseUrl($baseUrlOverride ?? $this->baseUrl())
                ->timeout(30);

            $response = $client->{$method}($path, $data);
            $body = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'message' => 'OK', 'raw' => $body];
            }

            // Body IDCloudHost kadang HTML (bukan JSON) kalau request
            // ditolak di layer proxy/WAF sebelum sampai ke aplikasi --
            // json() akan null, jangan diam-diam gagal.
            if ($body === null) {
                return [
                    'success' => false,
                    'message' => "HTTP {$response->status()}: " . \Illuminate\Support\Str::limit($response->body(), 300),
                    'raw'     => null,
                ];
            }

            $message = $body['errors']['Error'] ?? $body['message'] ?? $body['errors'] ?? $body;

            if (is_array($message)) {
                $message = json_encode($message);
            }

            $message = $message ?: "Permintaan ditolak (HTTP {$response->status()}).";

            return ['success' => false, 'message' => $message, 'raw' => $body];
        } catch (Throwable $e) {
            Log::warning("IDCloudHost API [{$method} {$path}] gagal: " . $e->getMessage(), ['server_id' => $this->server->id]);

            return ['success' => false, 'message' => 'Tidak bisa terhubung ke IDCloudHost: ' . $e->getMessage(), 'raw' => null];
        }
    }

    // ── HostingPanelInterface ──────────────────────────────────────

    /**
     * "Buat akun" untuk VM = buat VM baru sepenuhnya.
     *
     * $params['username']/['password'] dipakai sebagai login VM.
     * $params['domain'] dipakai sebagai nama/hostname VM (VM tidak
     * punya "domain" sungguhan seperti akun hosting -- cuma label).
     * $params['package'] berisi JSON spesifikasi (lihat decodePackage()).
     */
    public function createAccount(array $params): array
    {
        $spec = $this->decodePackage($params['package'] ?? '{}');

        // Nama VM di IDCloudHost cuma boleh huruf/angka/strip, dan tidak
        // boleh diawali/diakhiri strip -- domain klien sering mengandung
        // titik, jadi disaring dulu supaya tidak ditolak API.
        $vmName = preg_replace('/[^a-zA-Z0-9-]/', '-', $params['domain'] ?? 'vm-' . uniqid());
        $vmName = trim($vmName, '-') ?: 'vm-' . uniqid();

        $payload = array_filter([
            'name'               => $vmName,
            'os_name'            => $spec['os_name'],
            'os_version'         => $spec['os_version'],
            'disks'              => $spec['disk'],
            'vcpu'               => $spec['vcpu'],
            'ram'                => $spec['ram'],
            'username'           => $params['username'] ?? null,
            'password'           => $params['password'] ?? null,
            'billing_account_id' => $this->billingAccountId(),
        ], fn ($v) => $v !== null);

        $result = $this->call('post', '/user-resource/vm', $payload);

        if (! $result['success']) {
            return $result;
        }

        // uuid VM disimpan sebagai "username" di HostingAccount (lewat
        // kolom yang sama dipakai cPanel) -- itu satu-satunya pengenal
        // yang dibutuhkan semua operasi VM berikutnya (start/stop/dst).
        return [
            'success'  => true,
            'message'  => 'VM berhasil dibuat.',
            'raw'      => $result['raw'],
            'username' => $result['raw']['uuid'] ?? null,
            'ip'       => $result['raw']['public_ipv4'] ?? $result['raw']['public_ipv6'] ?? null,
        ];
    }

    /**
     * "Suspend" untuk VM = matikan (stop). Beda dari cPanel yang punya
     * status suspend tersendiri -- IDCloudHost tidak punya konsep itu,
     * jadi paling dekat adalah menghentikan VM (data tetap ada di disk,
     * cuma tidak berjalan / tidak menagih compute, tapi tetap menagih
     * storage).
     */
    public function suspendAccount(string $username, ?string $reason = null): array
    {
        return $this->call('post', '/user-resource/vm/stop', ['uuid' => $username]);
    }

    public function unsuspendAccount(string $username): array
    {
        return $this->call('post', '/user-resource/vm/start', ['uuid' => $username]);
    }

    public function terminateAccount(string $username): array
    {
        return $this->call('delete', '/user-resource/vm', ['uuid' => $username]);
    }

    /**
     * "Ganti paket" = ubah vcpu/ram VM. PENTING: API IDCloudHost cuma
     * mengizinkan ini saat VM berstatus stopped -- kalau masih running,
     * panggilan ini akan ditolak. Disk TIDAK bisa diperkecil/diperbesar
     * lewat endpoint ini (perlu Add Disk / Modify Disk terpisah).
     */
    public function changePackage(string $username, string $package): array
    {
        $spec = $this->decodePackage($package);

        return $this->call('patch', '/user-resource/vm', [
            'uuid' => $username,
            'vcpu' => $spec['vcpu'],
            'ram'  => $spec['ram'],
        ]);
    }

    public function testConnection(): array
    {
        return $this->call('get', '/user-resource/user');
    }

    // ── Operasi khusus VM (di luar interface bersama) ──────────────

    public function getVmInfo(string $uuid): array
    {
        return $this->call('get', '/user-resource/vm', ['uuid' => $uuid]);
    }

    /**
     * Dipakai fitur "Ganti Password" di dashboard klien -- sama seperti
     * changePanelPassword untuk cPanel. VM harus dalam keadaan running.
     */
    public function changePassword(string $uuid, string $username, string $password): array
    {
        return $this->call('patch', '/user-resource/vm/user', [
            'uuid'     => $uuid,
            'username' => $username,
            'password' => $password,
        ]);
    }

    /**
     * Instal ulang VM dari awal -- SEMUA data di disk utama hilang.
     * Dipakai kalau klien/admin ingin mulai bersih tanpa membuat VM baru
     * (IP & spesifikasi tetap sama).
     */
    public function reinstall(string $uuid, ?string $osName = null, ?string $osVersion = null): array
    {
        return $this->call('post', '/user-resource/vm/reinstall', array_filter([
            'uuid'       => $uuid,
            'os_name'    => $osName,
            'os_version' => $osVersion,
        ]));
    }

    /**
     * Daftar OS yang tersedia -- dipakai admin saat menyiapkan produk
     * VPS baru (pilihan os_name/os_version yang valid untuk dijual).
     * BUKAN location-specific menurut dokumentasi API, jadi jangan
     * pakai baseUrl() yang bisa kesisipan slug lokasi (dulu ini bug:
     * kalau Hostname/slug server diisi, request salah jadi
     * /v1/{slug}/config/vm_images/plain_os yang tidak valid).
     */
    public function listOsImages(): array
    {
        return $this->call('get', '/config/vm_images/plain_os', [], 'https://api.idcloudhost.com/v1');
    }

    /**
     * Daftar semua lokasi/datacenter IDCloudHost. Dipakai Diagnosa untuk
     * mengecek slug lokasi yang dikonfigurasi di kolom Hostname server
     * ini valid atau tidak, dan sebagai dasar listVmsAllLocations().
     */
    public function listLocations(): array
    {
        return $this->call('get', '/config/locations', [], 'https://api.idcloudhost.com/v1');
    }

    /**
     * Daftar semua VM di lokasi/akun ini (sesuai slug lokasi yang
     * dikonfigurasi di server ini) -- dipakai halaman Diagnosa khusus
     * IDCloudHost (pengganti "daftar paket & akun" ala cPanel, yang
     * tidak relevan untuk provider ini).
     */
    public function listVms(): array
    {
        return $this->call('get', '/user-resource/vm/list');
    }

    /**
     * VM list API IDCloudHost itu location-specific -- tidak ada
     * endpoint yang mengembalikan VM dari SEMUA lokasi sekaligus.
     * Method ini query tiap lokasi satu per satu supaya Diagnosa bisa
     * mendeteksi kasus "server dikonfigurasi ke lokasi yang salah" --
     * VM-nya ada, cuma nyasar di lokasi lain dari yang di kolom
     * Hostname server ini.
     */
    public function listVmsAllLocations(): array
    {
        $locationsResult = $this->listLocations();

        if (! $locationsResult['success']) {
            return ['success' => false, 'message' => $locationsResult['message'], 'by_location' => [], 'total' => 0];
        }

        $byLocation = [];
        $total = 0;

        foreach ($locationsResult['raw'] as $loc) {
            $slug = $loc['slug'] ?? '';
            $result = $this->call('get', '/user-resource/vm/list', [], "https://api.idcloudhost.com/v1/{$slug}");
            $count = $result['success'] ? count($result['raw'] ?? []) : null;

            $byLocation[] = [
                'slug'       => $slug,
                'name'       => $loc['display_name'] ?? $slug,
                'is_default' => (bool) ($loc['is_default'] ?? false),
                'vm_count'   => $count,
                'error'      => $result['success'] ? null : $result['message'],
            ];

            $total += $count ?? 0;
        }

        return ['success' => true, 'message' => 'OK', 'by_location' => $byLocation, 'total' => $total];
    }

    public function toggleAutoBackup(string $uuid): array
    {
        return $this->call('post', '/user-resource/vm/backup', ['uuid' => $uuid]);
    }

    // ── Billing & Diagnosa ──────────────────────────────────────────

    /**
     * Info akun billing IDCloudHost (sisa deposit, tagihan belum
     * dibayar, status akun/restriction_level, alasan freeze) -- BUKAN
     * location-specific.
     */
    public function getBillingAccount(): array
    {
        $id = $this->resolveBillingAccountId();

        if ($id === null) {
            return ['success' => false, 'message' => 'Tidak ada billing account ditemukan di akun IDCloudHost ini.', 'raw' => null];
        }

        return $this->call('get', '/payment/billing_account', ['billing_account_id' => $id], 'https://api.idcloudhost.com/v1');
    }

    /**
     * Resource pool (kelas server) yang tersedia di lokasi server ini --
     * dipakai kalau nanti createAccount() dikembangkan pakai
     * designated_pool_uuid, supaya bisa divalidasi UUID-nya benar-benar
     * ada.
     */
    public function listResourcePools(): array
    {
        return $this->call('get', '/user-resource/host_pool/list');
    }

    /**
     * Floating IP milik billing account ini -- dipakai Diagnosa buat
     * nemuin IP yang "nganggur" (assigned_to: null) tapi tetap kena
     * biaya jalan terus, mirip orphanWhmDomains di server cPanel.
     */
    public function listFloatingIps(): array
    {
        $id = $this->resolveBillingAccountId();

        return $this->call('get', '/network/ip_addresses', array_filter(['billing_account_id' => $id]));
    }

    /**
     * Harga cost dari IDCloudHost (bukan harga jual Lumora) -- dipakai
     * membandingkan dengan rate card server (price_per_vcpu_hour dkk)
     * supaya kelihatan kalau margin sudah tipis/negatif.
     */
    public function getPricingPolicy(): array
    {
        return $this->call('get', '/pricing/policy', [], 'https://api.idcloudhost.com/v1');
    }

    /**
     * Ubah struktur pricing/policy IDCloudHost (per numCpus/megsRam)
     * jadi angka per-unit yang gampang dibandingkan langsung dengan
     * kolom rate card di tabel servers (semuanya "per jam").
     */
    public function normalizePricingPolicy(array $policy): array
{
    $items = collect($policy);

    $cpu = $items->firstWhere('resourceType', 'CPU');

    $ramTier = $items->where('resourceType', 'RAM')->sortByDesc('megsRam')->first();
    $ramPerGb = null;

    if ($ramTier && isset($ramTier['price'], $ramTier['megsRam']) && $ramTier['megsRam'] > 0) {
        $ramPerGb = $ramTier['price'] / ($ramTier['megsRam'] / 1024);
    }

    $storageMain = $items->first(fn ($i) => ($i['resourceType'] ?? null) === 'STORAGE' && ($i['serviceNameInUptime'] ?? null) === 'main');
    $storageBackup = $items->first(fn ($i) => ($i['resourceType'] ?? null) === 'STORAGE' && ($i['serviceNameInUptime'] ?? null) === 'backup');
    $storageSnapshot = $items->first(fn ($i) => ($i['resourceType'] ?? null) === 'STORAGE' && ($i['serviceNameInUptime'] ?? null) === 'snapshot');
    $windows = $items->first(fn ($i) => ($i['resourceType'] ?? null) === 'LICENSE' && ($i['serviceNameInUptime'] ?? null) === 'windows');

    return [
        'vcpu_hour'         => $cpu['price'] ?? null,
        'ram_gb_hour'       => $ramPerGb,
        'storage_gb_hour'   => $storageMain['price'] ?? null,
        'backup_gb_hour'    => $storageBackup['price'] ?? null,
        'snapshot_gb_hour'  => $storageSnapshot['price'] ?? null,
        'windows_vcpu_hour' => $windows['price'] ?? null,
    ];
}

    /**
     * Total biaya & rincian pemakaian bulan berjalan per billing
     * account -- dipakai sanity-check sebelum closing period, ketauan
     * kalau ada VM yang biayanya melonjak tidak wajar.
     */
    public function getUsage(): array
    {
        $id = $this->resolveBillingAccountId();

        if ($id === null) {
            return ['success' => false, 'message' => 'Billing account ID tidak ditemukan.', 'raw' => null];
        }

        return $this->call('get', '/charging/usage', ['billing_account_id' => $id], 'https://api.idcloudhost.com/v1');
    }
}