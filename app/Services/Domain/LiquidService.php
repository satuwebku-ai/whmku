<?php

namespace App\Services\Domain;

use App\Models\Registrar;
use App\Services\Domain\Contracts\DomainRegistrarInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Integrasi Liqu.id (registrar software JogjaCamp / ResellerCamp).
 *
 * Endpoint & parameter di kelas ini diambil dari library PHP resmi
 * Liquid (github.com/liquidregistrar/liquid-php) dan dokumentasi
 * liquid-docs.readthedocs.io — bukan tebakan.
 *
 * ── Ringkasan API ──
 * Base URL   : https://api.liqu.id/v1          (produksi)
 *              https://api.domainsas.com/v1    (demo/sandbox)
 * Auth       : HTTP Basic Auth
 *              username = Reseller ID, password = API Key
 * Format     : JSON di semua respons (termasuk error)
 * Rate limit : ±100 request / 15 menit per reseller
 *
 * ── Catatan penting soal alur registrasi ──
 * Berbeda dari Namecheap yang menerima data kontak WHOIS mentah dalam
 * satu request, Liqu.id memakai model berjenjang:
 *
 *     customer  →  contact  →  domain
 *
 * Artinya untuk registrasi domain dibutuhkan `customer_id` dan empat
 * contact ID (registrant/billing/admin/tech). Service ini menanganinya
 * otomatis: kalau customer/contact belum ada, akan dibuatkan dulu dari
 * data kontak yang dikirim, baru domainnya didaftarkan.
 */
class LiquidService implements DomainRegistrarInterface
{
    /**
     * Base URL default kalau field "API URL" di form Registrar dikosongkan.
     */
    protected const DEFAULT_LIVE_URL = 'https://api.liqu.id/v1';
    protected const DEFAULT_DEMO_URL = 'https://api.domainsas.com/v1';

    public function __construct(protected Registrar $registrar) {}

    /**
     * GET /domains/availability?domain=a.com,b.com
     */
    public function checkAvailability(array $domains): array
    {
        $response = $this->call('get', '/domains/availability', [
            'domain' => implode(',', $domains),
        ]);

        if (! $response['success']) {
            return [
                'success' => false,
                'message' => $response['message'],
                'results' => [],
                'raw' => $response['raw'],
            ];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'results' => $this->parseAvailability($response['raw'], $domains),
            'raw' => $response['raw'],
        ];
    }

    /**
     * POST /domains
     *
     * Butuh customer_id + 4 contact ID, jadi dibuatkan dulu bila belum ada.
     */
    public function registerDomain(array $params): array
    {
        $c = $params['contact'];

        // 1. Pastikan customer ada (dicari berdasarkan email, dibuat kalau belum ada)
        $customer = $this->resolveCustomerId($c);

        if (! $customer['success']) {
            return ['success' => false, 'message' => 'Gagal menyiapkan customer di Liqu.id: ' . $customer['message'], 'raw' => $customer['raw']];
        }

        $customerId = $customer['customer_id'];

        // 2. Buat contact untuk customer tersebut
        $contact = $this->createContact($customerId, $c);

        if (! $contact['success']) {
            return ['success' => false, 'message' => 'Gagal membuat contact di Liqu.id: ' . $contact['message'], 'raw' => $contact['raw']];
        }

        $contactId = $contact['contact_id'];

        // 3. Daftarkan domain. Keempat peran contact memakai contact yang sama,
        //    praktik umum untuk registrasi standar.
        $payload = [
            'domain_name'           => $params['domain'],
            'customer_id'           => $customerId,
            'registrant_contact_id' => $contactId,
            'billing_contact_id'    => $contactId,
            'admin_contact_id'      => $contactId,
            'tech_contact_id'       => $contactId,
            'invoice_option'        => $params['invoice_option'] ?? 'no_invoice',
            'years'                 => $params['years'] ?? 1,
        ];

        if (! empty($params['nameservers'])) {
            $payload['ns'] = implode(',', (array) $params['nameservers']);
        }

        if (! empty($params['whois_privacy'])) {
            $payload['purchase_privacy_protection'] = 'true';
            $payload['privacy_protection_enabled'] = 'true';
        }

        return $this->call('post', '/domains', $payload);
    }

    /**
     * POST /domains/{domain_id}/renew
     *
     * Liqu.id memakai domain_id (numerik), bukan nama domain — jadi
     * dicari dulu ID-nya lewat /domains/details-by-name.
     */
    public function renewDomain(string $domain, int $years): array
    {
        $lookup = $this->findDomainId($domain);

        if (! $lookup['success']) {
            return ['success' => false, 'message' => $lookup['message'], 'raw' => $lookup['raw']];
        }

        return $this->call('post', "/domains/{$lookup['domain_id']}/renew", [
            'years'          => $years,
            'current_date'   => now()->format('Y-m-d'),
            'invoice_option' => 'no_invoice',
        ]);
    }

    /**
     * PUT /domains/{domain_id}/ns
     */
    public function setNameservers(string $domain, array $nameservers): array
    {
        $lookup = $this->findDomainId($domain);

        if (! $lookup['success']) {
            return ['success' => false, 'message' => $lookup['message'], 'raw' => $lookup['raw']];
        }

        return $this->call('put', "/domains/{$lookup['domain_id']}/ns", [
            'ns' => implode(',', $nameservers),
        ]);
    }

    /**
     * Uji kredensial dengan request ringan yang tidak mengubah data.
     */
    public function testConnection(): array
    {
        $result = $this->call('get', '/domains', ['limit' => 1]);

        if ($result['success']) {
            $result['message'] = 'Kredensial Liqu.id valid.';
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // Helper internal
    // ─────────────────────────────────────────────────────────────

    /**
     * Cari customer berdasarkan email; buat baru kalau belum ada.
     *
     * @return array{success: bool, customer_id: int|string|null, message: string, raw: mixed}
     */
    protected function resolveCustomerId(array $c): array
    {
        $existing = $this->call('get', '/customers', ['email' => $c['email'], 'limit' => 1]);

        if ($existing['success']) {
            $found = $this->firstRow($existing['raw']);

            if ($found && ! empty($found['customer_id'])) {
                return ['success' => true, 'customer_id' => $found['customer_id'], 'message' => 'OK', 'raw' => $existing['raw']];
            }
        }

        [$ccNo, $telNo] = $this->splitPhone($c['phone']);

        $created = $this->call('post', '/customers', [
            'email'          => $c['email'],
            'name'           => trim($c['first_name'] . ' ' . $c['last_name']),
            'password'       => $this->generatePassword(),
            'company'        => $c['company'] ?? trim($c['first_name'] . ' ' . $c['last_name']),
            'address_line_1' => $c['address'],
            'city'           => $c['city'],
            'state'          => $c['state'],
            'country_code'   => strtoupper($c['country']),
            'zipcode'        => $c['postal_code'],
            'tel_cc_no'      => $ccNo,
            'tel_no'         => $telNo,
        ]);

        $row = $this->firstRow($created['raw']);

        return [
            'success'     => $created['success'] && ! empty($row['customer_id']),
            'customer_id' => $row['customer_id'] ?? null,
            'message'     => $created['message'],
            'raw'         => $created['raw'],
        ];
    }

    /**
     * POST /customers/{customer_id}/contacts
     *
     * @return array{success: bool, contact_id: int|string|null, message: string, raw: mixed}
     */
    protected function createContact(int|string $customerId, array $c): array
    {
        [$ccNo, $telNo] = $this->splitPhone($c['phone']);

        $created = $this->call('post', "/customers/{$customerId}/contacts", [
            'name'           => trim($c['first_name'] . ' ' . $c['last_name']),
            'company'        => $c['company'] ?? trim($c['first_name'] . ' ' . $c['last_name']),
            'email'          => $c['email'],
            'address_line_1' => $c['address'],
            'city'           => $c['city'],
            'state'          => $c['state'],
            'country_code'   => strtoupper($c['country']),
            'zipcode'        => $c['postal_code'],
            'tel_cc_no'      => $ccNo,
            'tel_no'         => $telNo,
        ]);

        $row = $this->firstRow($created['raw']);

        return [
            'success'    => $created['success'] && ! empty($row['contact_id']),
            'contact_id' => $row['contact_id'] ?? null,
            'message'    => $created['message'],
            'raw'        => $created['raw'],
        ];
    }

    /**
     * GET /domains/details-by-name?domain_name=contoh.com
     *
     * @return array{success: bool, domain_id: int|string|null, message: string, raw: mixed}
     */
    protected function findDomainId(string $domain): array
    {
        $response = $this->call('get', '/domains/details-by-name', ['domain_name' => $domain]);

        $row = $this->firstRow($response['raw']);

        if (! $response['success'] || empty($row['domain_id'])) {
            return [
                'success' => false,
                'domain_id' => null,
                'message' => "Domain {$domain} tidak ditemukan di akun Liqu.id ini. " . $response['message'],
                'raw' => $response['raw'],
            ];
        }

        return ['success' => true, 'domain_id' => $row['domain_id'], 'message' => 'OK', 'raw' => $response['raw']];
    }

    /**
     * Ubah respons availability jadi map [domain => tersedia?].
     *
     * Liqu.id bisa mengembalikan beberapa bentuk, jadi ditangani fleksibel.
     */
    protected function parseAvailability(mixed $raw, array $requested): array
    {
        $results = [];

        if (! is_array($raw)) {
            return $results;
        }

        // Bentuk 1: map { "contoh.com": { "status": "available" } }
        foreach ($raw as $key => $value) {
            if (is_string($key) && str_contains($key, '.') && is_array($value)) {
                $results[$key] = $this->isAvailableFlag($value);
            }
        }

        if ($results) {
            return $results;
        }

        // Bentuk 2: array of objects [ { "domain": "contoh.com", "status": "available" } ]
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $item['domain'] ?? $item['domain_name'] ?? null;

            if ($name) {
                $results[$name] = $this->isAvailableFlag($item);
            }
        }

        return $results;
    }

    /**
     * Baca penanda ketersediaan dari berbagai kemungkinan nama field.
     */
    protected function isAvailableFlag(array $item): bool
    {
        if (isset($item['available'])) {
            return filter_var($item['available'], FILTER_VALIDATE_BOOLEAN);
        }

        $status = strtolower((string) ($item['status'] ?? ''));

        return in_array($status, ['available', 'true', 'yes'], true);
    }

    /**
     * Ambil baris pertama dari respons — Liqu.id mengembalikan array of
     * objects untuk endpoint list, tapi object tunggal untuk create.
     */
    protected function firstRow(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        // Object tunggal (punya key non-numerik)
        if (! array_is_list($raw)) {
            return $raw;
        }

        $first = $raw[0] ?? null;

        return is_array($first) ? $first : null;
    }

    /**
     * Pisahkan "+62.81234567890" atau "+6281234567890" jadi [kode negara, nomor].
     *
     * @return array{0: string, 1: string}
     */
    protected function splitPhone(string $phone): array
    {
        $phone = trim($phone);

        // Format Namecheap-style "+62.81234567890"
        if (str_contains($phone, '.')) {
            [$cc, $no] = explode('.', ltrim($phone, '+'), 2);

            return [preg_replace('/\D/', '', $cc), preg_replace('/\D/', '', $no)];
        }

        $digits = preg_replace('/\D/', '', $phone);

        // Nomor Indonesia: 62xxx atau 08xxx
        if (str_starts_with($digits, '62')) {
            return ['62', substr($digits, 2)];
        }

        if (str_starts_with($digits, '0')) {
            return ['62', ltrim($digits, '0')];
        }

        return ['62', $digits];
    }

    protected function generatePassword(): string
    {
        // Liqu.id mensyaratkan password customer yang cukup kuat.
        return \Illuminate\Support\Str::password(16, symbols: false) . 'aA1!';
    }

    /**
     * Panggil API Liqu.id dengan HTTP Basic Auth (Reseller ID : API Key).
     *
     * @return array{success: bool, message: string, raw: mixed}
     */
    protected function call(string $method, string $endpoint, array $params = []): array
    {
        try {
            $client = $this->client();

            $response = match ($method) {
                'post'  => $client->asForm()->post($endpoint, $params),
                'put'   => $client->asForm()->put($endpoint, $params),
                'delete' => $client->delete($endpoint, $params),
                default => $client->get($endpoint, $params),
            };

            $body = $response->json();

            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'message' => 'Autentikasi ditolak. Cek Reseller ID dan API Key.',
                    'raw' => $body,
                ];
            }

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $this->extractError($body) ?? "Liqu.id mengembalikan HTTP {$response->status()}.",
                    'raw' => $body ?? $response->body(),
                ];
            }

            return ['success' => true, 'message' => 'OK', 'raw' => $body];
        } catch (Throwable $e) {
            Log::warning("Liqu.id API [{$method} {$endpoint}] gagal: " . $e->getMessage(), [
                'registrar_id' => $this->registrar->id,
            ]);

            return [
                'success' => false,
                'message' => 'Tidak bisa terhubung ke Liqu.id: ' . $e->getMessage(),
                'raw' => null,
            ];
        }
    }

    /**
     * Format error Liqu.id: { "type": ..., "message": ..., "code": ... }
     */
    protected function extractError(mixed $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        $message = $body['message'] ?? null;
        $code = $body['code'] ?? null;

        if ($message && $code) {
            return "{$message} ({$code})";
        }

        return $message;
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withBasicAuth(
                (string) $this->registrar->api_username,   // Reseller ID
                (string) $this->registrar->api_key         // API Key
            )
            ->acceptJson()
            ->timeout(25);
    }

    protected function baseUrl(): string
    {
        if (filled($this->registrar->api_url)) {
            return rtrim($this->registrar->api_url, '/');
        }

        return $this->registrar->sandbox ? self::DEFAULT_DEMO_URL : self::DEFAULT_LIVE_URL;
    }
}
