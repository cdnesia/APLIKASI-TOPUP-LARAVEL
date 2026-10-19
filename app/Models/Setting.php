<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    /** Nilai bawaan bila belum diatur admin */
    public const DEFAULTS = [
        'site_name' => 'elhazmi.com',
        'site_tagline' => 'Top up game, pulsa, dan e-wallet — cepat, aman, harga bersahabat.',
        'whatsapp_admin' => '',
        'contact_email' => '',
        'contact_address' => '',
        'instagram' => '',
        'operational_hours' => 'Setiap hari, 08.00–22.00 WIB',
        'announcement' => '',
        'margin_percent' => '5',
        'margin_flat' => '0',
        'price_rounding' => '100',
        'order_expiry_hours' => '3',
        // Kode dari Google Search Console (metode verifikasi tag HTML)
        'google_site_verification' => '',
        // SEO beranda; kosong = dibuat otomatis
        'seo_home_title' => '',
        'seo_home_description' => '',
        // Template SEO halaman game. Variabel: {nama} {kategori} {publisher} {harga_mulai} {situs}
        'seo_brand_title' => 'Top Up {nama} Murah & Instan',
        'seo_brand_description' => 'Top up {nama} murah, aman, dan diproses otomatis dalam hitungan menit. Mulai {harga_mulai}. Cukup masukkan ID, bayar via QRIS, e-wallet, atau transfer bank.',
        // Path file di disk public; kosong = pakai logo/favicon bawaan
        'logo' => '',
        'logo_dark' => '',
        'favicon' => '',
        'og_image' => '',
    ];

    /** Pengaturan berupa file gambar yang diunggah dari admin */
    public const IMAGES = ['logo', 'logo_dark', 'favicon', 'og_image'];

    /** URL publik gambar pengaturan, atau null bila belum diunggah */
    public static function imageUrl(string $key): ?string
    {
        $path = static::get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }

        Cache::forget('settings');
    }
}
