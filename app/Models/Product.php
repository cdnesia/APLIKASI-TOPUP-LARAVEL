<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable(['brand_id', 'sku', 'name', 'image', 'category', 'type', 'base_price', 'selling_price', 'price_locked', 'is_active', 'provider_active', 'unlimited_stock', 'stock', 'description', 'synced_at'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'selling_price' => 'integer',
            'price_locked' => 'boolean',
            'is_active' => 'boolean',
            'provider_active' => 'boolean',
            'unlimited_stock' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** Flash sale yang sedang berlangsung untuk produk ini (bila ada) */
    public function flashSale(): HasOne
    {
        return $this->hasOne(FlashSale::class)->running()->orderBy('price');
    }

    /** Harga yang dibayar pembeli saat ini: harga flash sale bila berlangsung */
    public function currentPrice(): int
    {
        return $this->flashSale?->price ?? $this->selling_price;
    }

    /** Produk yang bisa dibeli: aktif di toko, aktif di Digiflazz, dan stok tersedia */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('provider_active', true)
            ->where(fn ($q) => $q->where('unlimited_stock', true)->orWhere('stock', '>', 0));
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->provider_active && ($this->unlimited_stock || $this->stock > 0);
    }

    /** Gambar nominal sendiri, atau logo brand bila belum diunggah */
    public function imageUrl(): ?string
    {
        if ($this->image) {
            return Storage::disk('public')->url($this->image);
        }

        return $this->brand?->imageUrl();
    }

    /** "MOBILE LEGENDS 5 Diamond" -> "5 Diamond" bila nama brand sudah terlihat di halaman */
    public function shortName(): string
    {
        $brand = $this->brand?->name;
        $short = $brand ? trim(preg_replace('/^'.preg_quote($brand, '/').'\\b/i', '', $this->name)) : '';

        return $short !== '' ? $short : $this->name;
    }

    public function profit(): int
    {
        return $this->selling_price - $this->base_price;
    }
}
