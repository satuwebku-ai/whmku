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

    public function testConnection(): array
    {
        return $this->call('version', []);
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
