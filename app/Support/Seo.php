<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Setting;

/** Nilai SEO bawaan yang dipakai bila admin tidak mengisinya sendiri */
class Seo
{
    /** Contoh nilai variabel template untuk pratinjau di admin */
    public static function exampleVars(): array
    {
        $brand = Brand::query()->active()->whereHas('products', fn ($q) => $q->available())
            ->orderByDesc('is_popular')->orderByRaw("category = 'Games' desc")->first();
        $min = $brand?->products()->available()->min('selling_price');

        return [
            '{nama}' => $brand->name ?? 'Mobile Legends',
            '{kategori}' => $brand->category ?? 'Games',
            '{publisher}' => $brand?->publisher ?: ($brand->name ?? 'Moonton'),
            '{harga_mulai}' => $min ? rupiah($min) : 'Rp5.000',
            '{situs}' => Setting::get('site_name'),
        ];
    }

    public static function defaultHomeTitle(): string
    {
        return Setting::get('site_name').' — Top Up Game, Pulsa & E-Wallet Murah & Instan';
    }

    /** ±150 karakter, menyebut 3 game populer agar relevan dengan pencarian */
    public static function defaultHomeDescription(): string
    {
        // Utamakan brand populer & kategori Games; nama kembar (mis. AXIS Pulsa/Data) cukup sekali
        $popular = Brand::query()->active()
            ->whereHas('products', fn ($q) => $q->available())
            ->orderByDesc('is_popular')->orderByRaw("category = 'Games' desc")->orderBy('sort_order')->orderBy('name')
            ->limit(20)->pluck('name')->unique()->take(3)->implode(', ');

        return 'Top up '.($popular ? "{$popular}, " : '').'pulsa, paket data, token PLN & e-wallet murah di '
            .Setting::get('site_name').'. Proses otomatis, bayar via QRIS & e-wallet.';
    }
}
