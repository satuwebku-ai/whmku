<?php

namespace App\Services\Hosting;

use App\Models\Server;
use App\Services\Hosting\Contracts\HostingPanelInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Integrasi cPanel/WHM lewat WHM API 1 (whostmgr JSON API).
 * Dokumentasi resmi: https://api.docs.cpanel.net/whm/introduction/
 *
 * Autentikasi pakai API Token (bukan password root), dibuat dari:
 * WHM » Development » Manage API Tokens.
 */
class CpanelWhmService implements HostingPanelInterface
{
    public function __construct(protected Server $server) {}

    public function createAccount(array $params): array
    {
        return $this->call('createacct', [
            'username' => $params['username'],
            'domain'   => $params['domain'],
            'password' => $params['password'],
            'plan'     => $params['package'],
            'contactemail' => $params['email'] ?? '',
        ]);
    }

    public function suspendAccount(string $username, ?string $reason = null): array
    {
        return $this->call('suspendacct', [
            'user'   => $username,
            'reason' => $reason ?? 'Disuspend oleh admin panel',
        ]);
    }

    public function unsuspendAccount(string $username): array
    {
        return $this->call('unsuspendacct', ['user' => $username]);
    }

    public function terminateAccount(string $username): array
    {
        return $this->call('removeacct', ['user' => $username]);
    }

    public function changePackage(string $username, string $package): array
    {
        return $this->call('changepackage', ['user' => $username, 'pkg' => $package]);
    }

    /**
     * Status SSL domain tertentu, lewat WHM API 1 `fetch_ssl_vhosts` —
     * fungsi ini sudah lama ada di WHM, tapi TIDAK sesering fungsi lain
     * yang sudah kita pakai (createacct, suspendacct, dst) saya
     * pastikan detail responsnya. Kalau field yang saya baca di sini
     * ternyata tidak cocok dengan punya server-mu, pakai
     * debugSslStatus() untuk lihat respons mentahnya, baru saya
     * perbaiki pemetaannya berdasarkan data sungguhan, bukan tebakan
     * kedua.
     */
    public function getSslStatus(string $domain): array
    {
        $result = $this->call('fetch_ssl_vhosts', []);

        if (! $result['success']) {
            return ['success' => false, 'message' => $result['message'], 'installed' => null, 'expires_at' => null, 'issuer' => null, 'raw' => $result['raw']];
        }

        $rows = $result['raw']['data'] ?? $result['raw'] ?? [];
        $rows = is_array($rows) ? $rows : [];

        $match = null;
        foreach ($rows as $row) {
            $rowDomain = $row['domain'] ?? $row['servername'] ?? null;
            if ($rowDomain === $domain) {
                $match = $row;
                break;
            }
        }

        if (! $match) {
            return ['success' => true, 'message' => 'Domain tidak ditemukan di daftar vhost SSL server.', 'installed' => false, 'expires_at' => null, 'issuer' => null, 'raw' => $rows];
        }

        $certSource = $match['certificate'] ?? $match;

        return [
            'success' => true,
            'message' => 'OK',
            'installed' => filled($certSource['not_after'] ?? $match['expiry'] ?? null),
            'expires_at' => $certSource['not_after'] ?? $match['expiry'] ?? null,
            'issuer' => $certSource['issuer'] ?? $match['issuer_name'] ?? null,
            'raw' => $match,
        ];
    }

    /**
     * Alat bantu sementara — lihat persis respons mentah fetch_ssl_vhosts
     * dari server, kalau getSslStatus() di atas ternyata salah baca field.
     */
    public function debugSslStatus(): array
    {
        return $this->call('fetch_ssl_vhosts', []);
    }

    public function testConnection(): array
    {
        return $this->call('version', []);
    }

    /**
     * Pemakaian disk akun, lewat WHM API 1 `accountsummary`.
     *
     * Field `diskused`/`disklimit` sudah stabil bertahun-tahun di WHM
     * (contoh: "65M", "500M", atau "unlimited") — tidak diubah jadi angka
     * murni di sini supaya format aslinya (satuan M/G, atau "unlimited")
     * tetap apa adanya, bukan ditebak-tebak.
     *
     * Bandwidth SENGAJA tidak disertakan: WHM modern tidak selalu melacak
     * bandwidth per akun (fitur itu makin jarang dipakai host), jadi
     * daripada menampilkan angka yang belum tentu akurat, kolom itu
     * dilewatkan sampai ada cara yang lebih pasti untuk memastikannya.
     */
    public function getAccountUsage(string $username): array
    {
        $result = $this->call('accountsummary', ['user' => $username]);

        if (! $result['success']) {
            return ['success' => false, 'message' => $result['message'], 'disk_used' => null, 'disk_limit' => null, 'raw' => $result['raw']];
        }

        $acct = $result['raw']['data']['acct'][0] ?? null;

        if (! $acct) {
            return ['success' => false, 'message' => 'Data akun tidak ditemukan di server.', 'disk_used' => null, 'disk_limit' => null, 'raw' => $result['raw']];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'disk_used' => $acct['diskused'] ?? null,
            'disk_limit' => $acct['disklimit'] ?? null,
            'ip' => $acct['ip'] ?? null,
            'raw' => $acct,
        ];
    }

    /**
     * Buat sesi login cPanel sekali klik (Single Sign-On).
     *
     * WHM API 1 `create_user_session` mengembalikan URL berisi token
     * sekali pakai, sehingga klien bisa masuk ke cPanel tanpa perlu tahu
     * password akunnya. Token kedaluwarsa otomatis dalam beberapa menit.
     *
     * Dokumentasi: https://api.docs.cpanel.net/openapi/whm/operation/create_user_session/
     *
     * @return array{success: bool, message: string, url: ?string, raw: mixed}
     */
    public function createSsoSession(string $username, string $service = 'cpaneld'): array
    {
        $result = $this->call('create_user_session', [
            'user'    => $username,
            'service' => $service,
        ]);

        $url = $result['raw']['data']['url'] ?? null;

        if (! $result['success'] || ! $url) {
            return [
                'success' => false,
                'message' => $result['message'] ?: 'Server tidak mengembalikan URL sesi.',
                'url' => null,
                'raw' => $result['raw'],
            ];
        }

        return ['success' => true, 'message' => 'OK', 'url' => $url, 'raw' => $result['raw']];
    }

    /**
     * Panggil WHM API 1 dengan autentikasi token.
     * Format header: Authorization: whm {api_username}:{api_token}
     */
    protected function call(string $function, array $params): array
    {
        try {
            $response = $this->client()
                ->get("/json-api/{$function}", array_merge($params, ['api.version' => 1]));

            $body = $response->json();
            $result = $body['metadata'] ?? null;

            $success = $response->successful()
                && (($result['result'] ?? null) === 1 || ($result['reason'] ?? null) === 'OK');

            return [
                'success' => $success,
                'message' => $result['reason'] ?? ($success ? 'Berhasil.' : 'Panel menolak permintaan (respons tidak dikenali).'),
                'raw'     => $body,
            ];
        } catch (Throwable $e) {
            Log::warning("WHM API [{$function}] gagal: " . $e->getMessage(), ['server_id' => $this->server->id]);

            return [
                'success' => false,
                'message' => 'Tidak bisa terhubung ke server WHM: ' . $e->getMessage(),
                'raw'     => null,
            ];
        }
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->server->api_base_url)
            ->withHeaders([
                'Authorization' => "whm {$this->server->api_username}:{$this->server->api_token}",
            ])
            ->withOptions(['verify' => $this->server->verify_ssl])
            ->timeout(15);
    }
}
