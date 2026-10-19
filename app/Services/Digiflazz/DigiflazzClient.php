<?php

namespace App\Services\Digiflazz;

use App\Models\ApiLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Klien API Digiflazz (https://developer.digiflazz.com/api/buyer).
 * Signature setiap request: md5(username + key + <suffix>).
 */
class DigiflazzClient
{
    public function __construct(
        protected string $baseUrl,
        protected ?string $username,
        protected ?string $key,
        protected bool $testing,
        protected ?string $webhookSecret,
    ) {}

    public static function fromConfig(): self
    {
        $config = config('services.digiflazz');

        return new self(
            rtrim($config['base_url'], '/'),
            $config['username'],
            $config['key'],
            filter_var($config['testing'], FILTER_VALIDATE_BOOL),
            $config['webhook_secret'],
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->username) && filled($this->key);
    }

    public function isTesting(): bool
    {
        return $this->testing;
    }

    /** Saldo deposit Digiflazz */
    public function balance(): int
    {
        $data = $this->post('/cek-saldo', [
            'cmd' => 'deposit',
            'sign' => $this->sign('depo'),
        ]);

        return (int) ($data['deposit'] ?? 0);
    }

    /**
     * Daftar harga produk prabayar.
     * Catatan: Digiflazz membatasi frekuensi request price list (beberapa menit sekali).
     */
    public function priceList(): array
    {
        $data = $this->post('/price-list', [
            'cmd' => 'prepaid',
            'sign' => $this->sign('pricelist'),
        ]);

        // Saat error, "data" berupa objek berisi rc & message, bukan array produk
        if (! array_is_list($data)) {
            throw new DigiflazzException($data['message'] ?? 'Format price list tidak dikenali.');
        }

        return $data;
    }

    /**
     * Buat transaksi. Mengirim ulang dengan ref_id yang sama = cek status transaksi.
     */
    public function transaction(string $sku, string $customerNo, string $refId): array
    {
        $payload = [
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $this->sign($refId),
        ];

        if ($this->testing) {
            $payload['testing'] = true;
        }

        return $this->post('/transaction', $payload);
    }

    /** Verifikasi header X-Hub-Signature: "sha1=" + HMAC-SHA1(body, secret) */
    public function verifyWebhook(string $rawBody, ?string $signature): bool
    {
        if (blank($this->webhookSecret) || blank($signature)) {
            return false;
        }

        $expected = 'sha1='.hash_hmac('sha1', $rawBody, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    protected function sign(string $suffix): string
    {
        return md5($this->username.$this->key.$suffix);
    }

    protected function post(string $path, array $payload): array
    {
        if (! $this->isConfigured()) {
            ApiLog::record(['provider' => 'digiflazz', 'direction' => 'out', 'endpoint' => $path, 'success' => false, 'message' => 'Kredensial Digiflazz belum diatur di file .env.']);

            throw new DigiflazzException('Kredensial Digiflazz belum diatur di file .env.');
        }

        $payload = ['username' => $this->username] + $payload;
        // ref_id percobaan ulang berbentuk INVOICE-2, simpan nomor invoicenya saja
        $log = ['provider' => 'digiflazz', 'direction' => 'out', 'endpoint' => $path, 'request' => $payload,
            'invoice' => isset($payload['ref_id']) ? explode('-', $payload['ref_id'])[0] : null];
        $start = microtime(true);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(60)
                ->post($this->baseUrl.$path, $payload);
        } catch (ConnectionException $e) {
            ApiLog::record($log + ['success' => false, 'message' => 'Koneksi gagal: '.$e->getMessage(), 'duration_ms' => $this->elapsed($start)]);

            throw new DigiflazzException('Tidak dapat terhubung ke Digiflazz: '.$e->getMessage(), previous: $e);
        }

        $data = $response->json('data');
        $log += ['http_status' => $response->status(), 'duration_ms' => $this->elapsed($start), 'response' => $response->json() ?? $response->body()];

        if (! is_array($data)) {
            ApiLog::record($log + ['success' => false, 'message' => 'Respons tidak valid']);

            throw new DigiflazzException("Respons Digiflazz tidak valid (HTTP {$response->status()}).");
        }

        // rc 00 = sukses, 03 = pending; rc lain = gagal. Price list (array produk) tidak punya rc
        $rc = array_is_list($data) ? null : ($data['rc'] ?? null);
        ApiLog::record($log + [
            'success' => $response->successful() && in_array($rc, [null, '00', '03'], true),
            'message' => array_is_list($data) ? count($data).' produk' : trim(($data['status'] ?? '').' '.($data['message'] ?? '')),
        ]);

        return $data;
    }

    protected function elapsed(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
