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

    protected function client()
    {
        return Http::withHeaders(['apikey' => $this->server->api_token])
            ->baseUrl($this->baseUrl())
            ->timeout(30);
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

    protected function billingAccountId(): ?string
    {
        $id = trim((string) $this->server->api_username);

        return $id !== '' ? $id : null;
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
            'pool_uuid'      => $spec['pool_uuid'] ?? null,
            'location'       => $spec['location'] ?? null,
        ];
    }

    protected function call(string $method, string $path, array $data = []): array
    {
        try {
            $response = $this->client()->{$method}($path, $data);
            $body = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'message' => 'OK', 'raw' => $body];
            }

            $message = $body['errors']['Error'] ?? $body['message'] ?? "Permintaan ditolak (HTTP {$response->status()}).";

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
            'name'                 => $vmName,
            'os_name'              => $spec['os_name'],
            'os_version'           => $spec['os_version'],
            'disks'                => $spec['disk'],
            'vcpu'                 => $spec['vcpu'],
            'ram'                  => $spec['ram'],
            'username'             => $params['username'] ?? null,
            'password'             => $params['password'] ?? null,
            'billing_account_id'   => $this->billingAccountId(),
            // Kelas server (resource pool) yang dipilih admin -- kalau
            // kosong, provider memakai pool default-nya.
            'designated_pool_uuid' => $spec['pool_uuid'] ?? null,
        ], fn ($v) => $v !== null);

        $result = $this->call('post', '/user-resource/vm', $payload);

        if (! $result['success']) {
            return $result;
        }

        // uuid VM disimpan sebagai "username" di HostingAccount (lewat
        // kolom yang sama dipakai cPanel) -- itu satu-satunya pengenal
        // yang dibutuhkan semua operasi VM berikutnya (start/stop/dst).
        return [
            'success' => true,
            'message' => 'VM berhasil dibuat.',
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
     */
    public function listOsImages(): array
    {
        return $this->call('get', '/config/vm_images/plain_os');
    }

    /**
     * Daftar semua VM di lokasi/akun ini -- dipakai halaman Diagnosa
     * khusus IDCloudHost (pengganti "daftar paket & akun" ala cPanel,
     * yang tidak relevan untuk provider ini).
     */
    public function listVms(): array
    {
        return $this->call('get', '/user-resource/vm/list');
    }

    // ── Endpoint diagnosa (semua GET, hanya membaca) ───────────────
    // Semua path di bawah diambil langsung dari dokumentasi resmi
    // https://api.idcloudhost.com/ -- lihat catatan khusus di
    // creditCandidates() untuk yang belum terkonfirmasi.

    /** Info akun/user pemilik API key ini. */
    public function getUserInfo(): array
    {
        return $this->callGlobal('get', '/user-resource/user');
    }

    /** Daftar lokasi datacenter + mana yang default. */
    public function listLocations(): array
    {
        return $this->callGlobal('get', '/config/locations');
    }

    /** Kelas server / resource pool (mis. General, Performance). */
    public function listHostPools(): array
    {
        return $this->call('get', '/user-resource/host_pool/list');
    }

    /**
     * Batasan parameter VM: min/max vCPU, RAM, disk, dan daftar OS
     * yang valid -- ini "aturan main" yang HARUS dipatuhi saat menyusun
     * paket VPS untuk dijual, supaya tidak membuat paket yang ditolak
     * API saat provisioning.
     */
    public function getVmParameters(): array
    {
        return $this->callGlobal('get', '/api/parameters/vm');
    }

    /** Semua image OS (plain OS + app catalog). */
    public function listVmImages(): array
    {
        return $this->callGlobal('get', '/config/vm_images');
    }

    /** Image App Catalog saja (mis. WordPress siap pakai). */
    public function listAppCatalogImages(): array
    {
        return $this->callGlobal('get', '/config/vm_images/app_catalog');
    }

    /** ISO bootable (rescue / installer). */
    public function listBootImages(): array
    {
        return $this->callGlobal('get', '/config/boot_images');
    }

    /** Block storage / disk milik akun. */
    public function listDisks(): array
    {
        return $this->call('get', '/storage/disks');
    }

    /** Floating IP (IP publik yang bisa dipindah antar VM). */
    public function listFloatingIps(): array
    {
        return $this->call('get', '/network/ip_addresses');
    }

    /** Private network milik akun. */
    public function listNetworks(): array
    {
        return $this->call('get', '/network/networks');
    }

    /**
     * Daftar billing account + saldo kredit masing-masing.
     * Field penting: credit_amount, unpaid_amount, running_totals,
     * is_default, restriction_level, suspend_reason.
     */
    public function listBillingAccounts(): array
    {
        return $this->callGlobal('get', '/payment/billing_account/list');
    }

    /** Detail satu billing account (termasuk saldo & tunggakan). */
    public function getBillingAccountDetails(int|string $billingAccountId): array
    {
        return $this->callGlobal('get', '/payment/billing_account', ['billing_account_id' => $billingAccountId]);
    }

    /** Total tagihan yang belum dibayar (sudah termasuk pajak). */
    public function getUnpaidAmount(int|string $billingAccountId): array
    {
        return $this->callGlobal('get', '/payment/billing_account/unpaid_amount', ['billing_account_id' => $billingAccountId]);
    }

    /** Riwayat mutasi kredit (topup & pemakaian). */
    public function listCredit(int|string $billingAccountId): array
    {
        return $this->callGlobal('get', '/payment/credit/list', ['billing_account_id' => $billingAccountId]);
    }

    /**
     * HARGA MODAL per komponen dari IDCloudHost -- strukturnya persis
     * sama dengan formula harga jual kita (CPU, RAM, STORAGE main/
     * backup/snapshot, LICENSE windows), jadi bisa langsung
     * dibandingkan untuk menghitung margin.
     *
     * resourceType yang dikembalikan: CPU, RAM, STORAGE
     * (serviceNameInUptime: main/backup/snapshot), LICENSE (windows),
     * OBJECT_STORAGE. Semua harga per JAM.
     */
    public function getPricingPolicy(): array
    {
        return $this->callGlobal('get', '/pricing/policy');
    }

    /** Pemakaian & biaya bulan berjalan per resource. */
    public function getResourceUsage(int|string $billingAccountId): array
    {
        return $this->callGlobal('get', '/charging/usage', ['billing_account_id' => $billingAccountId]);
    }

    /**
     * Panggilan ke endpoint yang TIDAK location-specific (config, user,
     * parameters, payment, pricing) -- ini harus tanpa slug lokasi di
     * URL-nya, beda dengan resource VM/network yang wajib pakai slug.
     */
    protected function callGlobal(string $method, string $path, array $data = []): array
    {
        try {
            $response = Http::withHeaders(['apikey' => $this->server->api_token])
                ->baseUrl('https://api.idcloudhost.com/v1')
                ->timeout(30)
                ->{$method}($path, $data);

            $body = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'message' => 'OK', 'raw' => $body];
            }

            $message = $body['errors']['Error'] ?? $body['message'] ?? "Permintaan ditolak (HTTP {$response->status()}).";

            return ['success' => false, 'message' => $message, 'raw' => $body];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Tidak bisa terhubung: ' . $e->getMessage(), 'raw' => null];
        }
    }

    public function toggleAutoBackup(string $uuid): array
    {
        return $this->call('post', '/user-resource/vm/backup', ['uuid' => $uuid]);
    }
}
