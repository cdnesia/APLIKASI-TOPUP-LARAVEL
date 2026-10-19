<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function show(Brand $brand): View
    {
        abort_unless($brand->is_active, 404);

        $products = $brand->products()->available()->with('flashSale')->orderBy('selling_price')->get()
            ->each->setRelation('brand', $brand);

        // Kelompok nominal: Flash Sale di atas, sisanya per tipe dari Digiflazz (mis. Umum, Membership)
        $groups = collect();
        $flash = $products->filter(fn (Product $p) => $p->flashSale);
        if ($flash->isNotEmpty()) {
            $groups->put('Flash Sale', $flash->sortBy(fn (Product $p) => $p->flashSale->price)->values());
        }
        $regular = $products->reject(fn (Product $p) => $p->flashSale)->groupBy(fn (Product $p) => $p->type ?: 'Umum');
        foreach ($regular as $type => $items) {
            $groups->put($regular->count() > 1 ? $this->typeLabel($type) : 'Pilih Nominal', $items->values());
        }

        return view('brand', [
            'brand' => $brand,
            'groups' => $groups,
            'hasProducts' => $products->isNotEmpty(),
            'selected' => (int) request('item'),
            'paymentMethods' => PaymentMethod::query()->active()->get(),
        ]);
    }

    protected function typeLabel(string $type): string
    {
        return match (strtolower($type)) {
            'umum' => 'Top Up',
            'membership' => 'Membership & Pass',
            default => $type,
        };
    }
}
