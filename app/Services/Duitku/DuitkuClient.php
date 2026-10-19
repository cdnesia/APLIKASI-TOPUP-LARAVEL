<?php

namespace App\Services\Duitku;

use App\Models\ApiLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Klien API Duitku (https://docs.duitku.com/api/id/).
 * Semua signature: HMAC-SHA256(stringToSign, apiKey) — metode md5 lama sudah usang.
 */
class DuitkuClient
{
    public function __construct(
        protected ?string $merchantCode,
        protected ?string $apiKey,
        protected bool $sandbox,
    ) {}

    public static function fromConfig(): self
    {
        $config = config('services.duitku');

        return new self(
            $config['merchant_code'],
            $config['api_key'],
            filter_var($config['sandbox'], FILTER_VALIDATE_BOOL),
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->merchantCode) && filled($this->apiKey);
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Buat tagihan. Mengembalikan antara lain: reference, paymentUrl, vaNumber, qrString, amount.
     *
     * @param  array{method: string, order_id: string, amount: int, product: string, email: string, phone: string, name: string, callback_url: string, return_url: string, expiry_minutes: int}  $params
     */
    public function createInvoice(array $params): array
    {
        $data = $this->post('/v2/inquiry', [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $params['amount'],
            'paymentMethod' => $params['method'],
            'merchantOrderId' => $params['order_id'],
            'productDetails' => $params['product'],
            'email' => $params['email'],
            'phoneNumber' => $params['phone'],
            'customerVaName' => $params['name'],
            'callbackUrl' => $params['callback_url'],
            'returnUrl' => $params['return_url'],
            'expiryPeriod' => $params['expiry_minutes'],
            'signature' => $this->sign($this->merchantCode.$params['order_id'].$params['amount']),
        ]);

        if (($data['statusCode'] ?? null) !== '00') {
            throw new DuitkuException($data['statusMessage'] ?? $data['Message'] ?? 'Gagal membuat tagihan Duitku.');
        }

        return $data;
    }

    /** Cek status: statusCode "00" = lunas, "01" = menunggu, "02" = batal/kedaluwarsa */
    public function transactionStatus(string $orderId): array
    {
        return $this->post('/transactionStatus', [
            'merchantCode' => $this->merchantCode,
            'merchantOrderId' => $orderId,
            'signature' => $this->sign($this->merchantCode.$orderId),
        ]);
    }

    public function verifyCallback(array $payload): bool
    {
        if (! $this->isConfigured() || blank($payload['signature'] ?? null)) {
            return false;
        }

        $expected = $this->sign(($payload['merchantCode'] ?? '').($payload['amount'] ?? '').($payload['merchantOrderId'] ?? ''));

        return ($payload['merchantCode'] ?? null) === $this->merchantCode
            && hash_equals($expected, strtolower((string) $payload['signature']));
    }

    protected function sign(string $stringToSign): string
    {
        return hash_hmac('sha256', $stringToSign, (string) $this->apiKey);
    }

    protected function baseUrl(): string
    {
        return $this->sandbox
            ? 'https://sandbox.duitku.com/webapi/api/merchant'
            : 'https://passport.duitku.com/webapi/api/merchant';
    }

    protected function post(string $path, array $payload): array
    {
        if (! $this->isConfigured()) {
            ApiLog::record(['provider' => 'duitku', 'direction' => 'out', 'endpoint' => $path, 'success' => false, 'message' => 'Kredensial Duitku belum diatur di file .env.']);

            throw new DuitkuException('Kredensial Duitku belum diatur di file .env.');
        }

        $log = ['provider' => 'duitku', 'direction' => 'out', 'endpoint' => $path, 'request' => $payload, 'invoice' => $payload['merchantOrderId'] ?? null];
        $start = microtime(true);

        try {
            $response = Http::acceptJson()->asJson()->timeout(30)->post($this->baseUrl().$path, $payload);
        } catch (ConnectionException $e) {
            ApiLog::record($log + ['success' => false, 'message' => 'Koneksi gagal: '.$e->getMessage(), 'duration_ms' => (int) round((microtime(true) - $start) * 1000)]);

            throw new DuitkuException('Tidak dapat terhubung ke Duitku: '.$e->getMessage(), previous: $e);
        }

        $data = $response->json();
        $log += ['http_status' => $response->status(), 'duration_ms' => (int) round((microtime(true) - $start) * 1000), 'response' => $data ?? $response->body()];

        if (! is_array($data)) {
            ApiLog::record($log + ['success' => false, 'message' => 'Respons tidak valid']);

            throw new DuitkuException("Respons Duitku tidak valid (HTTP {$response->status()}).");
        }

        // Error validasi Duitku dikirim dengan HTTP 4xx dan field "Message"
        if ($response->failed()) {
            $message = $data['Message'] ?? $data['statusMessage'] ?? "Duitku menolak permintaan (HTTP {$response->status()}).";
            ApiLog::record($log + ['success' => false, 'message' => $message]);

            throw new DuitkuException($message);
        }

        // statusCode: 00 = sukses/lunas, 01 = menunggu pembayaran (wajar saat cek status)
        $code = $data['statusCode'] ?? null;
        ApiLog::record($log + [
            'success' => $code === '00' || ($code === '01' && $path === '/transactionStatus'),
            'message' => trim(($code ? "[{$code}] " : '').($data['statusMessage'] ?? '')),
        ]);

        return $data;
    }
}
