<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\Domain\DomainRegistrarFactory;
use App\Services\Hosting\HostingPanelFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function services(Request $request): View
    {
        $services = Auth::guard('client')->user()
            ->hostingAccounts()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.services.index', compact('services'));
    }

    public function service(HostingAccount $service): View
    {
        // Pastikan layanan ini benar-benar milik klien yang sedang login.
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        $service->load('orders', 'serverModel');

        // Pemakaian disk hanya diambil untuk akun aktif yang benar-benar
        // terhubung server (bukan akun manual) — supaya klien tidak
        // menunggu panggilan API yang pasti gagal untuk akun tanpa server.
        $usage = null;

        if ($service->status === 'active' && $service->serverModel && $service->username) {
            $panel = HostingPanelFactory::make($service->serverModel);

            if (method_exists($panel, 'getAccountUsage')) {
                $result = $panel->getAccountUsage($service->username);
                $usage = $result['success'] ? $result : null;
            }
        }

        return view('client.services.show', compact('service', 'usage'));
    }

    public function domains(Request $request): View
    {
        $domains = Auth::guard('client')->user()
            ->domains()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.domains.index', compact('domains'));
    }

    public function domain(Domain $domain): View
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        // Status kunci diambil langsung dari registrar (real-time), bukan
        // disimpan di kolom database — supaya selalu sesuai kondisi
        // sungguhan, bukan cuma catatan yang bisa ketinggalan zaman.
        $lockStatus = null;

        if ($domain->registrar && $domain->status === 'active') {
            $service = DomainRegistrarFactory::make($domain->registrar);

            if (method_exists($service, 'getDomainLockStatus')) {
                $result = $service->getDomainLockStatus($domain->domain_name);
                $lockStatus = $result['success'] ? $result['locked'] : null;
            }
        }

        return view('client.domains.show', compact('domain', 'lockStatus'));
    }

    /**
     * Login sekali klik ke cPanel.
     *
     * Server membuat sesi berisi token sekali pakai, lalu klien langsung
     * diarahkan ke sana — tidak perlu tahu password akun cPanel-nya.
     */
    public function loginPanel(HostingAccount $service): RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        if ($service->status !== 'active') {
            return back()->with('error', 'Layanan ini sedang tidak aktif, jadi belum bisa diakses.');
        }

        if (! $service->serverModel || ! $service->username) {
            return back()->with('error', 'Layanan ini belum terhubung ke server. Silakan hubungi support.');
        }

        $panel = HostingPanelFactory::make($service->serverModel);

        if (! method_exists($panel, 'createSsoSession')) {
            return back()->with('error', 'Panel ' . $service->serverModel->panel . ' belum mendukung login sekali klik.');
        }

        $result = $panel->createSsoSession($service->username);

        if (! $result['success']) {
            return back()->with('error', 'Gagal membuat sesi login: ' . $result['message']);
        }

        // away() dipakai karena tujuannya di luar aplikasi ini.
        return redirect()->away($result['url']);
    }

    /**
     * Ubah nameserver domain lewat API registrar.
     */
    public function updateNameservers(Request $request, Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        $data = $request->validate([
            'nameservers'   => ['required', 'array', 'min:2', 'max:5'],
            'nameservers.*' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'],
        ], [
            'nameservers.min' => 'Minimal dua nameserver harus diisi.',
            'nameservers.*.regex' => 'Format nameserver tidak valid. Contoh: ns1.contoh.com',
        ]);

        // Buang baris kosong, lalu pastikan tetap ada minimal dua.
        $nameservers = array_values(array_filter(
            array_map('trim', $data['nameservers']),
            fn ($ns) => $ns !== ''
        ));

        if (count($nameservers) < 2) {
            return back()->with('error', 'Minimal dua nameserver harus diisi.');
        }

        if ($domain->status !== 'active') {
            return back()->with('error', 'Nameserver hanya bisa diubah untuk domain yang aktif.');
        }

        if (! $domain->registrar) {
            return back()->with('error', 'Domain ini tidak terhubung ke registrar. Silakan hubungi support.');
        }

        $result = DomainRegistrarFactory::make($domain->registrar)
            ->setNameservers($domain->domain_name, $nameservers);

        if (! $result['success']) {
            return back()->with('error', 'Gagal mengubah nameserver: ' . $result['message']);
        }

        $domain->update(['nameservers' => $nameservers]);

        return back()->with('success', 'Nameserver berhasil diperbarui. Perubahan DNS bisa memakan waktu hingga 24 jam untuk menyebar.');
    }

    /**
     * Klien menyalakan/mematikan perpanjangan otomatis domainnya sendiri.
     * Sebelumnya kolom ini hanya bisa dilihat, tidak bisa diubah klien —
     * satu-satunya jalan adalah menghubungi support, padahal ini murni
     * preferensi klien sendiri, tidak ada alasan untuk melibatkan admin.
     */
    public function toggleDomainAutoRenew(Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        $domain->update(['auto_renew' => ! $domain->auto_renew]);

        return back()->with('success', $domain->auto_renew
            ? 'Perpanjangan otomatis diaktifkan. Invoice akan dibuat otomatis mendekati tanggal kedaluwarsa.'
            : 'Perpanjangan otomatis dimatikan. Anda perlu memperpanjang domain secara manual sebelum kedaluwarsa.');
    }

    /**
     * Nyalakan/matikan ID Protection setelah domain aktif — bukan hanya
     * bisa dipilih sekali di awal saat checkout.
     *
     * Method ini spesifik Liqu.id (belum tentu didukung registrar lain),
     * jadi dicek lewat method_exists sebelum dipanggil — sama seperti pola
     * yang dipakai untuk fitur QRIS tertanam di Duitku.
     */
    public function togglePrivacyProtection(Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        if (! $domain->registrar) {
            return back()->with('error', 'Domain ini tidak terhubung ke registrar. Silakan hubungi support.');
        }

        $service = DomainRegistrarFactory::make($domain->registrar);
        $turnOn = ! $domain->whois_privacy;
        $method = $turnOn ? 'enablePrivacyProtection' : 'disablePrivacyProtection';

        if (! method_exists($service, $method)) {
            return back()->with('error', 'Registrar domain ini belum mendukung pengaturan ID Protection lewat sistem.');
        }

        $result = $service->{$method}($domain->domain_name);

        if (! $result['success']) {
            return back()->with('error', 'Gagal mengubah ID Protection: ' . $result['message']);
        }

        $domain->update(['whois_privacy' => $turnOn]);

        return back()->with('success', $turnOn
            ? 'ID Protection diaktifkan — data WHOIS Anda disembunyikan dari publik.'
            : 'ID Protection dimatikan.');
    }

    /**
     * Nyalakan/matikan Registrar Lock — mengunci domain dari transfer
     * tanpa sepengetahuan pemilik. Endpoint ini sempat dikira tidak ada
     * di API Liqu.id sampai ditemukan lewat spesifikasi resmi mereka
     * (/domains/{domain_id}/locked).
     */
    public function toggleDomainLock(Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        if (! $domain->registrar) {
            return back()->with('error', 'Domain ini tidak terhubung ke registrar. Silakan hubungi support.');
        }

        $service = DomainRegistrarFactory::make($domain->registrar);

        if (! method_exists($service, 'lockDomain')) {
            return back()->with('error', 'Registrar domain ini belum mendukung Registrar Lock lewat sistem.');
        }

        // Status sekarang diambil langsung dari registrar (bukan disimpan
        // di database kita) — ini satu-satunya sumber kebenaran, supaya
        // tidak ada kondisi "menurut sistem kita aktif, tapi sebenarnya
        // di registrar tidak".
        $status = $service->getDomainLockStatus($domain->domain_name);
        $turnOn = ! ($status['locked'] ?? false);

        $result = $turnOn
            ? $service->lockDomain($domain->domain_name, 'Dikunci oleh klien lewat panel.')
            : $service->unlockDomain($domain->domain_name);

        if (! $result['success']) {
            return back()->with('error', 'Gagal mengubah Registrar Lock: ' . $result['message']);
        }

        return back()->with('success', $turnOn
            ? 'Registrar Lock diaktifkan — domain tidak bisa dipindah ke registrar lain sampai dimatikan.'
            : 'Registrar Lock dimatikan. Domain sekarang bisa ditransfer.');
    }

    /**
     * Minta kode transfer (EPP/Auth Code). Dibuat ke sesi, bukan
     * disimpan permanen di database — kode ini setara password sekali
     * pakai untuk transfer domain, semakin sedikit jejaknya semakin baik.
     */
    public function requestAuthCode(Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        if (! $domain->registrar) {
            return back()->with('error', 'Domain ini tidak terhubung ke registrar. Silakan hubungi support.');
        }

        $service = DomainRegistrarFactory::make($domain->registrar);

        if (! method_exists($service, 'getAuthCode')) {
            return back()->with('error', 'Registrar domain ini belum mendukung pengambilan kode transfer lewat sistem. Silakan hubungi support.');
        }

        $result = $service->getAuthCode($domain->domain_name);

        if (! $result['success'] || ! $result['code']) {
            return back()->with('error', 'Gagal mengambil kode transfer: ' . $result['message']);
        }

        return back()->with('auth_code', $result['code']);
    }

    // ── DNS Management ──────────────────────────────────────────────

    public function dns(Domain $domain): View|RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        if (! $domain->registrar) {
            return redirect()->route('client.domains.show', $domain)
                ->with('error', 'Domain ini tidak terhubung ke registrar, DNS tidak bisa dikelola dari sini.');
        }

        $service = DomainRegistrarFactory::make($domain->registrar);

        if (! method_exists($service, 'listDnsRecords')) {
            return redirect()->route('client.domains.show', $domain)
                ->with('error', 'Registrar domain ini belum mendukung manajemen DNS lewat sistem.');
        }

        $result = $service->listDnsRecords($domain->domain_name);

        return view('client.domains.dns', [
            'domain' => $domain,
            'records' => $result['records'],
            'warning' => $result['success'] ? null : $result['message'],
            'types' => array_keys(\App\Services\Domain\LiquidService::DNS_TYPES),
        ]);
    }

    public function addDnsRecord(Request $request, Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        $data = $request->validate([
            'type'     => ['required', 'in:A,CNAME,MX,TXT'],
            'hostname' => ['required', 'string', 'max:255'],
            'value'    => ['required', 'string', 'max:500'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $service = DomainRegistrarFactory::make($domain->registrar);

        if (! method_exists($service, 'addDnsRecord')) {
            return back()->with('error', 'Registrar domain ini belum mendukung manajemen DNS.');
        }

        $result = $service->addDnsRecord(
            $domain->domain_name,
            $data['type'],
            $data['hostname'],
            $data['value'],
            $data['priority'] ?? null,
        );

        return back()->with($result['success'] ? 'success' : 'error',
            $result['success'] ? 'Record DNS berhasil ditambahkan.' : 'Gagal menambah record: ' . $result['message']);
    }

    public function deleteDnsRecord(Request $request, Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        $data = $request->validate([
            'type'     => ['required', 'in:A,CNAME,MX,TXT'],
            'hostname' => ['required', 'string'],
            'value'    => ['required', 'string'],
        ]);

        $service = DomainRegistrarFactory::make($domain->registrar);

        if (! method_exists($service, 'deleteDnsRecord')) {
            return back()->with('error', 'Registrar domain ini belum mendukung manajemen DNS.');
        }

        $result = $service->deleteDnsRecord($domain->domain_name, $data['type'], $data['hostname'], $data['value']);

        return back()->with($result['success'] ? 'success' : 'error',
            $result['success'] ? 'Record DNS berhasil dihapus.' : 'Gagal menghapus record: ' . $result['message']);
    }

    /**
     * Ajukan pembatalan layanan — belum menghentikan apapun, hanya masuk
     * antrean tinjauan admin. Ini disengaja: pembatalan otomatis berisiko
     * mematikan layanan yang masih dibutuhkan hanya karena klik yang salah
     * atau permintaan yang berubah pikiran.
     */
    public function requestCancellation(Request $request, HostingAccount $service): RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        if ($service->hasPendingCancellation()) {
            return back()->with('error', 'Sudah ada pengajuan pembatalan yang sedang ditinjau untuk layanan ini.');
        }

        if ($service->status === 'terminated') {
            return back()->with('error', 'Layanan ini sudah tidak aktif.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->update([
            'cancellation_status' => 'requested',
            'cancellation_reason' => $data['reason'],
            'cancellation_requested_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan pembatalan berhasil dikirim. Tim kami akan meninjau dalam 1x24 jam.');
    }

    /**
     * Batalkan pengajuan pembatalan yang belum diproses admin.
     */
    public function withdrawCancellation(HostingAccount $service): RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        if (! $service->hasPendingCancellation()) {
            return back()->with('error', 'Tidak ada pengajuan pembatalan yang aktif.');
        }

        $service->update([
            'cancellation_status' => 'none',
            'cancellation_reason' => null,
            'cancellation_requested_at' => null,
        ]);

        return back()->with('success', 'Pengajuan pembatalan dibatalkan.');
    }

    // ── Upgrade Paket Mandiri ──────────────────────────────────────

    /**
     * Halaman pilih paket tujuan upgrade.
     */
    public function upgradeForm(HostingAccount $service): View|RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        if ($service->status !== 'active') {
            return redirect()->route('client.services.show', $service)
                ->with('error', 'Upgrade hanya bisa dilakukan untuk layanan yang sedang aktif.');
        }

        if ($service->pending_upgrade_invoice_id) {
            return redirect()->route('client.services.show', $service)
                ->with('error', 'Sudah ada permintaan upgrade yang menunggu pembayaran. Selesaikan itu dulu, atau hubungi support untuk membatalkannya.');
        }

        $options = $service->upgradeEligibleProducts();

        return view('client.services.upgrade', [
            'service' => $service,
            'options' => $options,
        ]);
    }

    /**
     * Klien memilih paket tujuan — dibuatkan invoice prorata, BELUM
     * benar-benar upgrade. Upgrade sungguhan baru terjadi otomatis
     * setelah invoice ini lunas — lihat
     * ProvisioningService::processUpgradePayment().
     */
    public function requestUpgrade(Request $request, HostingAccount $service): RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $eligible = $service->upgradeEligibleProducts();
        $newProduct = $eligible->firstWhere('id', (int) $data['product_id']);

        if (! $newProduct) {
            return back()->with('error', 'Paket yang dipilih tidak tersedia untuk upgrade dari paket Anda saat ini.');
        }

        $amount = $service->prorateUpgrade($newProduct);

        if ($amount <= 0) {
            return back()->with('error', 'Terjadi kesalahan menghitung biaya upgrade. Silakan hubungi support.');
        }

        $invoice = Invoice::create([
            'client_id' => $service->client_id,
            'amount' => $amount,
            'tax' => 0,
            'discount' => 0,
            'status' => 'unpaid',
            'issue_date' => now(),
            'due_date' => now()->addDays(3),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Upgrade {$service->domain}: {$service->product?->name} → {$newProduct->name} (prorata sisa siklus)",
            'amount' => $amount,
        ]);

        $service->update([
            'pending_upgrade_product_id' => $newProduct->id,
            'pending_upgrade_invoice_id' => $invoice->id,
        ]);

        return redirect()->route('client.invoices.show', $invoice)
            ->with('success', "Invoice upgrade dibuat — Rp " . number_format($amount, 0, ',', '.') . ". Paket akan diganti otomatis setelah dibayar.");
    }

    /**
     * Batalkan permintaan upgrade yang belum dibayar.
     */
    public function cancelUpgrade(HostingAccount $service): RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        if (! $service->pending_upgrade_invoice_id) {
            return back()->with('error', 'Tidak ada permintaan upgrade yang aktif.');
        }

        // Invoice-nya ikut dibatalkan supaya tidak menggantung sebagai
        // tagihan yatim yang tidak akan pernah diproses.
        $service->pendingUpgradeInvoice?->update(['status' => 'cancelled']);

        $service->update([
            'pending_upgrade_product_id' => null,
            'pending_upgrade_invoice_id' => null,
        ]);

        return back()->with('success', 'Permintaan upgrade dibatalkan.');
    }

    // ── Perpanjang Sekarang ──────────────────────────────────────────

    /**
     * Klien minta invoice perpanjangan dibuat sekarang, tidak menunggu
     * jadwal otomatis H-7. Dipakai baik dari halaman Layanan maupun
     * Domain — dua method terpisah karena tipe modelnya beda, tapi
     * logikanya sama-sama tinggal panggil createRenewalInvoice() yang
     * sudah dipakai bersama perintah terjadwal.
     */
    public function renewServiceNow(HostingAccount $service): RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        if ($service->status !== 'active') {
            return back()->with('error', 'Hanya layanan aktif yang bisa diperpanjang.');
        }

        if ($service->renewal_invoice_id) {
            return redirect()->route('client.invoices.show', $service->renewal_invoice_id)
                ->with('error', 'Sudah ada invoice perpanjangan yang menunggu dibayar.');
        }

        $invoice = $service->createRenewalInvoice();

        return redirect()->route('client.invoices.show', $invoice)
            ->with('success', 'Invoice perpanjangan dibuat. Masa aktif diperpanjang otomatis setelah dibayar.');
    }

    public function renewDomainNow(Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        if ($domain->status !== 'active') {
            return back()->with('error', 'Hanya domain aktif yang bisa diperpanjang.');
        }

        if ($domain->renewal_invoice_id) {
            return redirect()->route('client.invoices.show', $domain->renewal_invoice_id)
                ->with('error', 'Sudah ada invoice perpanjangan yang menunggu dibayar.');
        }

        $invoice = $domain->createRenewalInvoice();

        return redirect()->route('client.invoices.show', $invoice)
            ->with('success', 'Invoice perpanjangan dibuat. Masa aktif diperpanjang otomatis setelah dibayar.');
    }
}
