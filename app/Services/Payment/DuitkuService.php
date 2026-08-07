<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Integrasi Duitku lewat Invoice API v2 — mendukung VA, e-wallet (OVO,
 * DANA, ShopeePay), QRIS, dan kartu kredit lewat satu halaman pembayaran,
 * mirip pola Midtrans Snap / Xendit Invoice.
 *
 * Dokumentasi resmi: https://docs.duitku.com
 * (kalau alamat API di bawah berubah suatu saat, cek dokumentasi itu —
 * Duitku jarang mengubahnya, tapi bukan tidak mungkin.)
 *
 * Field yang dipakai (mengikuti kolom generik yang sudah ada, supaya
 * tidak perlu migrasi tambahan):
 *   - server_key → API Key      (Dashboard Duitku → Pengaturan → API Key)
 *   - client_key → Merchant Code
 *
 * Autentikasi tidak memakai Bearer token, melainkan tanda tangan MD5 yang
 * disertakan di tiap body permintaan — kombinasi berbeda tergantung jenis
 * permintaannya (lihat masing-masing method).
 */
class DuitkuService implements PaymentGatewayInterface
{
    protected const SANDBOX_URL = 'https://sandbox.duitku.com';
    protected const PRODUCTION_URL = 'https://passport.duitku.com';

    public function __construct(protected PaymentGateway $gateway) {}

    public function createTransaction(Payment $payment): array
    {
        $client = $payment->client;
        $merchantCode = (string) $this->gateway->client_key;
        $apiKey = (string) $this->gateway->server_key;

        // Duitku mewajibkan nominal berupa bilangan bulat (tanpa desimal)
        // untuk perhitungan tanda tangan maupun payload-nya.
        $amount = (int) round((float) $payment->total);
        $orderId = $payment->reference;

        $signature = md5($merchantCode . $orderId . $amount . $apiKey);

        $payload = [
            'merchantCode'    => $merchantCode,
            'paymentAmount'   => $amount,
            'merchantOrderId' => $orderId,
            'productDetails'  => 'Invoice ' . ($payment->invoice->invoice_number ?? $orderId),
            'email'           => $client->email ?? 'pelanggan@example.com',
            'customerVaName'  => $client->name ?? 'Pelanggan',
            'phoneNumber'     => $client->phone ?? null,
            // paymentMethod sengaja tidak diisi — Duitku akan menampilkan
            // halaman pilihan metode sendiri, konsisten dengan pola
            // Midtrans Snap / Xendit Invoice yang sudah ada.
            'callbackUrl'     => route('payment.webhook', ['driver' => 'duitku']),
            'returnUrl'       => route('payment.finish', ['reference' => $orderId]),
            'signature'       => $signature,
        ];

        try {
            $response = $this->client()->post('/webapi/api/merchant/v2/inquiry', $payload);
            $body = $response->json();

            if (! $response->successful() || ($body['statusCode'] ?? null) === '01') {
                return [
                    'success' => false,
                    'message' => $body['statusMessage'] ?? "Duitku mengembalikan HTTP {$response->status()}.",
                    'payment_url' => null,
                    'external_id' => null,
                    'raw' => $body,
                ];
            }

            return [
                'success'     => true,
                'message'     => 'Transaksi Duitku berhasil dibuat.',
                'payment_url' => $body['paymentUrl'] ?? null,
                'external_id' => $body['reference'] ?? null,
                'raw'         => $body,
            ];
        } catch (Throwable $e) {
            Log::warning('Duitku createTransaction gagal: ' . $e->getMessage(), ['payment_id' => $payment->id]);

            return [
                'success' => false,
                'message' => 'Tidak bisa terhubung ke Duitku: ' . $e->getMessage(),
                'payment_url' => null,
                'external_id' => null,
                'raw' => null,
            ];
        }
    }

    /**
     * Duitku memverifikasi callback lewat tanda tangan MD5 yang disertakan
     * di body permintaan itu sendiri (bukan header terpisah seperti
     * Xendit) — dihitung ulang di sini dan dicocokkan.
     */
    public function handleCallback(Request $request): array
    {
        $data = $request->all();

        $merchantCode = (string) $this->gateway->client_key;
        $apiKey = (string) $this->gateway->server_key;

        $amount = (string) ($data['amount'] ?? '');
        $orderId = (string) ($data['merchantOrderId'] ?? '');
        $signature = (string) ($data['signature'] ?? '');

        $expected = md5($merchantCode . $amount . $orderId . $apiKey);

        if (blank($signature) || ! hash_equals($expected, $signature)) {
            Log::warning('Duitku callback ditolak: signature tidak cocok.', ['order_id' => $orderId]);

            return ['success' => false, 'message' => 'Signature tidak valid.', 'status' => null, 'payment' => null];
        }

        if (! $orderId) {
            return ['success' => false, 'message' => 'merchantOrderId tidak ada di payload.', 'status' => null, 'payment' => null];
        }

        $payment = Payment::where('reference', $orderId)->first();

        if (! $payment) {
            return ['success' => false, 'message' => "Pembayaran {$orderId} tidak ditemukan.", 'status' => null, 'payment' => null];
        }

        $status = $this->mapResultCode((string) ($data['resultCode'] ?? ''));

        if ($status === 'paid') {
            $payment->markAsPaid($data['paymentCode'] ?? 'Duitku', $data);
        } else {
            $payment->update([
                'status' => $status,
                'external_id' => $data['reference'] ?? $payment->external_id,
                'gateway_response' => $data,
            ]);
        }

        return ['success' => true, 'message' => 'Webhook diproses.', 'status' => $status, 'payment' => $payment];
    }

    public function checkStatus(Payment $payment): array
    {
        $merchantCode = (string) $this->gateway->client_key;
        $apiKey = (string) $this->gateway->server_key;
        $orderId = $payment->reference;

        $signature = md5($merchantCode . $orderId . $apiKey);

        try {
            $response = $this->client()->post('/webapi/api/merchant/transactionStatus', [
                'merchantCode' => $merchantCode,
                'merchantOrderId' => $orderId,
                'signature' => $signature,
            ]);

            $body = $response->json();

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $body['statusMessage'] ?? 'Gagal mengambil status dari Duitku.',
                    'status' => null,
                    'raw' => $body,
                ];
            }

            return [
                'success' => true,
                'message' => 'OK',
                'status' => $this->mapResultCode((string) ($body['statusCode'] ?? '')),
                'raw' => $body,
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Tidak bisa terhubung ke Duitku: ' . $e->getMessage(), 'status' => null, 'raw' => null];
        }
    }

    /**
     * "00" konsisten dipakai Duitku sebagai kode sukses, baik di respons
     * transactionStatus (statusCode) maupun payload callback (resultCode).
     */
    protected function mapResultCode(string $code): string
    {
        return match ($code) {
            '00' => 'paid',
            '01' => 'initiated', // menunggu pembayaran
            '02' => 'expired',  // batal / kedaluwarsa
            default => 'failed',
        };
    }

    protected function client(): PendingRequest
    {
        $base = $this->gateway->isSandbox() ? self::SANDBOX_URL : self::PRODUCTION_URL;

        return Http::baseUrl($base)
            ->acceptJson()
            ->asJson()
            ->timeout(25);
    }
}
