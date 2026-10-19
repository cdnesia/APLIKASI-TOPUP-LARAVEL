<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Duitku\PaymentMethodStatus;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(protected PaymentMethodStatus $paymentStatus) {}

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

        // Status online/offline Duitku per metode (id => true/false/null); offline ditaruh paling bawah
        $onlineCodes = $this->paymentStatus->onlineCodes();
        $paymentMethods = PaymentMethod::query()->active()->get();
        $paymentStatus = $paymentMethods->mapWithKeys(fn (PaymentMethod $m) => [$m->id => $this->paymentStatus->isOnline($m, $onlineCodes)]);
        $paymentMethods = $paymentMethods->sortBy(fn (PaymentMethod $m) => $paymentStatus[$m->id] === false ? 1 : 0)->values();

        return view('brand', [
            'brand' => $brand,
            'groups' => $groups,
            'hasProducts' => $products->isNotEmpty(),
            'selected' => (int) request('item'),
            'paymentMethods' => $paymentMethods,
            'paymentStatus' => $paymentStatus,
            'seo' => $this->seo($brand, $products),
        ]);
    }

    /** Judul, deskripsi, & data terstruktur halaman game agar menarik di hasil pencarian Google */
    protected function seo(Brand $brand, Collection $products): array
    {
        $site = Setting::get('site_name');
        $prices = $products->map(fn (Product $p) => $p->currentPrice());
        $vars = [
            '{nama}' => $brand->name,
            '{kategori}' => $brand->category,
            '{publisher}' => $brand->publisher ?: $brand->name,
            '{harga_mulai}' => $prices->isNotEmpty() ? rupiah($prices->min()) : 'harga terjangkau',
            '{situs}' => $site,
        ];
        $fill = fn (string $template) => trim(strtr($template, $vars));

        $schema = [[
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $brand->category, 'item' => route('home', ['kategori' => $brand->category])],
                ['@type' => 'ListItem', 'position' => 3, 'name' => "Top Up {$brand->name}", 'item' => route('brands.show', $brand)],
            ],
        ]];

        if ($prices->isNotEmpty()) {
            $schema[] = [
                '@type' => 'Product',
                'name' => "Top Up {$brand->name}",
                'description' => "Top up {$brand->name} resmi & instan di {$site}.",
                'image' => $brand->coverUrl() ?? $brand->imageUrl() ?? asset('og-image.png'),
                'brand' => ['@type' => 'Brand', 'name' => $brand->publisher ?: $brand->name],
                'offers' => [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => 'IDR',
                    'lowPrice' => $prices->min(),
                    'highPrice' => $prices->max(),
                    'offerCount' => $prices->count(),
                    'availability' => 'https://schema.org/InStock',
                    'seller' => ['@id' => url('/').'#organization'],
                ],
            ];
        }

        return [
            // Isian khusus brand > template di Pengaturan
            'title' => $fill($brand->seo_title ?: Setting::get('seo_brand_title')),
            'description' => $fill($brand->seo_description ?: Setting::get('seo_brand_description')),
            'image' => $brand->coverUrl() ?? $brand->bannerUrl() ?? $brand->imageUrl(),
            'schema' => $schema,
        ];
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
