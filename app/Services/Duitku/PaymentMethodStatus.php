<?php

namespace App\Services\Duitku;

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Cache;

/**
 * Status online/offline metode Duitku. Duitku hanya mengembalikan metode yang sedang tersedia
 * di proyek, jadi metode otomatis yang tidak ada di daftar dianggap offline.
 */
class PaymentMethodStatus
{
    /** Batas tunggu (detik) agar halaman order tidak lambat saat Duitku lambat */
    protected const TIMEOUT = 5;

    public function __construct(protected DuitkuClient $duitku) {}

    /**
     * Daftar mentah dari Duitku, di-cache 5 menit. Melempar DuitkuException bila gagal.
     *
     * @return array<int, array{paymentMethod: string, paymentName: string, paymentImage: string, totalFee: string}>
     */
    public function methods(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget($this->key());
            Cache::forget($this->key().'.failed');
        }

        return Cache::remember($this->key(), now()->addMinutes(5), fn () => $this->duitku->paymentMethods(timeout: self::TIMEOUT));
    }

    /** Kode metode yang sedang online; null bila status tidak bisa dicek (Duitku belum diatur / gangguan) */
    public function onlineCodes(): ?array
    {
        if (! $this->duitku->isConfigured() || Cache::has($this->key().'.failed')) {
            return null;
        }

        try {
            return array_column($this->methods(), 'paymentMethod');
        } catch (DuitkuException) {
            // Jangan coba ulang di setiap kunjungan halaman selama Duitku bermasalah
            Cache::put($this->key().'.failed', true, now()->addMinute());

            return null;
        }
    }

    /** true = online, false = offline, null = manual atau status tidak diketahui (tetap boleh dipilih) */
    public function isOnline(PaymentMethod $method, ?array $onlineCodes = null): ?bool
    {
        if (! $method->isAutomatic()) {
            return null;
        }

        $codes = $onlineCodes ?? $this->onlineCodes();

        return $codes === null ? null : in_array($method->gateway_code, $codes, true);
    }

    protected function key(): string
    {
        return 'duitku.payment-methods.'.md5(json_encode(config('services.duitku')));
    }
}
