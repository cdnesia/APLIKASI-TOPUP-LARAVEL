<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
    ];

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
