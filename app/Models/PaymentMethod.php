<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'type', 'gateway_code', 'account_number', 'account_name', 'image', 'instructions', 'fee', 'is_active', 'sort_order'])]
class PaymentMethod extends Model
{
    public const TYPES = [
        'bank' => 'Transfer Bank',
        'ewallet' => 'E-Wallet',
        'qris' => 'QRIS',
        'retail' => 'Gerai Retail',
    ];

    /** Kode metode pembayaran Duitku yang umum dipakai (lihat dashboard Duitku > Proyek) */
    public const GATEWAY_CODES = [
        'SP' => 'QRIS (ShopeePay)',
        'NQ' => 'QRIS (Nobu)',
        'BC' => 'VA BCA',
        'M2' => 'VA Mandiri',
        'I1' => 'VA BNI',
        'BR' => 'VA BRI',
        'BT' => 'VA Permata',
        'VA' => 'VA Maybank',
        'BV' => 'VA BSI',
        'S1' => 'VA Sampoerna',
        'NC' => 'VA BNC',
        'AG' => 'VA Artha Graha',
        'B1' => 'VA CIMB Niaga',
        'A1' => 'VA ATM Bersama',
        'DA' => 'DANA',
        'OV' => 'OVO',
        'SA' => 'ShopeePay',
        'LA' => 'LinkAja',
        'IR' => 'Indomaret',
        'FT' => 'Alfamart / Pos / Pegadaian',
    ];

    /** Jenis metode untuk kode Duitku; kode yang tidak dikenal dianggap Virtual Account (bank) */
    public static function typeForGateway(string $code): string
    {
        return match ($code) {
            'SP', 'NQ', 'GQ', 'SQ', 'DQ' => 'qris',
            'OV', 'SA', 'LA', 'DA', 'SL', 'OL', 'JP' => 'ewallet',
            'IR', 'FT' => 'retail',
            default => 'bank',
        };
    }

    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Metode Duitku dengan nominal minimum Rp10.000 (semua VA & gerai retail) */
    public const GATEWAY_MIN_10K = ['BC', 'M2', 'I1', 'BR', 'BT', 'B1', 'A1', 'VA', 'AG', 'S1', 'NC', 'BV', 'IR', 'FT'];

    /** Total minimum yang diterima metode ini (0 = tanpa batas) */
    public function minAmount(): int
    {
        return in_array($this->gateway_code, self::GATEWAY_MIN_10K, true) ? 10_000 : 0;
    }

    /** Dibayar otomatis lewat Duitku (bukan transfer manual + upload bukti) */
    public function isAutomatic(): bool
    {
        return filled($this->gateway_code);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    /**
     * Logo untuk ditampilkan: unggahan admin, atau logo bawaan sesuai kode Duitku / jenis.
     * Gambar QRIS manual adalah kode QR, jadi tidak dipakai sebagai logo.
     */
    public function logoUrl(): ?string
    {
        if ($this->image && ! ($this->type === 'qris' && ! $this->isAutomatic())) {
            return $this->imageUrl();
        }

        return self::defaultLogoUrl($this->gateway_code, $this->type);
    }

    /** Logo bawaan di public/images/payments/{kode}.svg; QRIS tanpa kode memakai qris.svg */
    public static function defaultLogoUrl(?string $gatewayCode, ?string $type = null): ?string
    {
        $file = match (true) {
            filled($gatewayCode) => $gatewayCode,
            $type === 'qris' => 'qris',
            default => null,
        };

        return $file && is_file(public_path("images/payments/{$file}.svg")) ? asset("images/payments/{$file}.svg") : null;
    }

    /** Data yang disalin ke order agar instruksi bayar tetap sama walau metode diubah */
    public function snapshot(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'gateway_code' => $this->gateway_code,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'image' => $this->image,
            'instructions' => $this->instructions,
        ];
    }
}
