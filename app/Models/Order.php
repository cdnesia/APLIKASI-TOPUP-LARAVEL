<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'invoice', 'product_id', 'brand_id', 'payment_method_id', 'flash_sale_id', 'brand_name', 'product_name', 'sku',
    'customer_no', 'customer_display', 'whatsapp', 'payment_snapshot', 'base_price', 'selling_price',
    'fee', 'unique_code', 'total', 'payment_status', 'payment_proof', 'gateway_reference', 'pay_code', 'qr_string', 'checkout_url', 'paid_at', 'expires_at', 'status',
    'ref_id', 'attempts', 'sn', 'provider_rc', 'provider_message', 'provider_price', 'processed_at',
    'completed_at', 'admin_note',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'payment_snapshot' => 'array',
            'payment_status' => PaymentStatus::class,
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function getRouteKeyName(): string
    {
        return 'invoice';
    }

    public static function generateInvoice(): string
    {
        do {
            $invoice = 'ELH'.now()->format('ymd').Str::upper(Str::random(6));
        } while (static::query()->where('invoice', $invoice)->exists());

        return $invoice;
    }

    public function profit(): int
    {
        // Laba bersih memakai harga aktual Digiflazz bila sudah ada
        return $this->selling_price - ($this->provider_price ?? $this->base_price);
    }

    /** Pesanan dibayar lewat payment gateway (lunas otomatis via callback) */
    public function isAutomaticPayment(): bool
    {
        return filled($this->payment_snapshot['gateway_code'] ?? null);
    }

    public function canUploadProof(): bool
    {
        return ! $this->isAutomaticPayment()
            && in_array($this->payment_status, [PaymentStatus::Unpaid, PaymentStatus::WaitingConfirmation])
            && ! $this->isExpired();
    }

    public function canBeConfirmed(): bool
    {
        return in_array($this->payment_status, [PaymentStatus::Unpaid, PaymentStatus::WaitingConfirmation]);
    }

    public function isExpired(): bool
    {
        return $this->payment_status === PaymentStatus::Unpaid && $this->expires_at?->isPast();
    }

    /** Logo metode bayar dari snapshot; gambar QRIS manual (kode QR) tidak dipakai sebagai logo */
    public function paymentLogoUrl(): ?string
    {
        $pay = $this->payment_snapshot ?? [];
        $automatic = filled($pay['gateway_code'] ?? null);

        if (! empty($pay['image']) && ($automatic || ($pay['type'] ?? null) !== 'qris')) {
            return Storage::disk('public')->url($pay['image']);
        }

        return PaymentMethod::defaultLogoUrl($pay['gateway_code'] ?? null, $pay['type'] ?? null);
    }

    public function paymentImageUrl(): ?string
    {
        $image = $this->payment_snapshot['image'] ?? null;

        return $image ? Storage::disk('public')->url($image) : null;
    }
}
