<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'price', 'quota', 'sold', 'starts_at', 'ends_at', 'is_active'])]
class FlashSale extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quota' => 'integer',
            'sold' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Sedang berlangsung dan kuota masih tersedia */
    public function scopeRunning(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->whereColumn('sold', '<', 'quota');
    }

    public function isRunning(): bool
    {
        return $this->is_active && $this->starts_at->isPast() && $this->ends_at->isFuture() && $this->sold < $this->quota;
    }

    public function remaining(): int
    {
        return max(0, $this->quota - $this->sold);
    }

    public function soldPercent(): int
    {
        return $this->quota ? (int) min(100, round($this->sold / $this->quota * 100)) : 100;
    }

    public function discountPercent(int $normalPrice): int
    {
        return $normalPrice > 0 ? (int) max(0, round((1 - $this->price / $normalPrice) * 100)) : 0;
    }

    public function status(): string
    {
        return match (true) {
            ! $this->is_active => 'Nonaktif',
            $this->starts_at->isFuture() => 'Terjadwal',
            $this->ends_at->isPast() => 'Selesai',
            $this->sold >= $this->quota => 'Habis',
            default => 'Berlangsung',
        };
    }
}
