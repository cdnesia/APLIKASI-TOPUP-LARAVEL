<?php

namespace App\Services;

use App\Models\Setting;

class PricingService
{
    /** Harga jual = harga modal + margin persen + margin tetap, dibulatkan ke atas */
    public function sellingPrice(int $basePrice): int
    {
        $percent = (float) Setting::get('margin_percent');
        $flat = (int) Setting::get('margin_flat');
        $rounding = max(1, (int) Setting::get('price_rounding'));

        $price = $basePrice + ($basePrice * $percent / 100) + $flat;

        return (int) (ceil($price / $rounding) * $rounding);
    }
}
