<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'publisher', 'slug', 'category', 'image', 'banner', 'cover', 'description', 'field_label', 'zone_label', 'input_hint', 'is_active', 'is_popular', 'sort_order'])]
class Brand extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? Storage::disk('public')->url($this->cover) : null;
    }

    public function bannerUrl(): ?string
    {
        return $this->banner ? Storage::disk('public')->url($this->banner) : null;
    }

    /** Ikon bawaan per kategori, dipakai bila logo belum diunggah */
    public function categoryIcon(): string
    {
        return self::categoryIconFor($this->category);
    }

    public static function categoryIconFor(?string $category): string
    {
        $category = strtolower((string) $category);

        return match (true) {
            str_contains($category, 'game') => 'puzzle',
            str_contains($category, 'pulsa'), str_contains($category, 'sms'), str_contains($category, 'masa aktif'), str_contains($category, 'perdana') => 'device-phone',
            str_contains($category, 'data') => 'wifi',
            str_contains($category, 'money') => 'wallet',
            str_contains($category, 'pln') => 'bolt',
            str_contains($category, 'voucher') => 'ticket',
            str_contains($category, 'tv') => 'tv',
            str_contains($category, 'gas') => 'fire',
            default => 'cube',
        };
    }

    /** Inisial untuk placeholder logo, mis. "MOBILE LEGENDS" -> "ML" */
    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->take(2)
            ->map(fn ($word) => mb_substr($word, 0, 1))
            ->implode('');
    }
}
