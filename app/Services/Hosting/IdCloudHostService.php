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
            'vcpu'       => (int) ($spec['vcpu'] ?? 1),
            'ram'        => (int) ($spec['ram'] ?? 1024),
            'disk'       => (int) ($spec['disk'] ?? 20),
            'os_name'    => $spec['os_name'] ?? 'ubuntu',
            'os_version' => $spec['os_version'] ?? '22.04',
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

    public function toggleAutoBackup(string $uuid): array
    {
        return $this->call('post', '/user-resource/vm/backup', ['uuid' => $uuid]);
    }
}
