<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /** Peta situs untuk Google Search Console: halaman statis + semua game/produk aktif */
    public function sitemap(): Response
    {
        $pages = collect([
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('price-list'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => route('guide'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => route('track'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => route('contact'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => route('terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
        ]);

        $brands = Brand::query()->active()
            ->whereHas('products', fn ($q) => $q->available())
            ->get(['id', 'slug', 'is_popular', 'updated_at'])
            ->map(fn (Brand $brand) => [
                'loc' => route('brands.show', $brand),
                'lastmod' => $brand->updated_at?->toAtomString(),
                'priority' => $brand->is_popular ? '0.9' : '0.7',
                'changefreq' => 'daily',
            ]);

        return response()
            ->view('seo.sitemap', ['urls' => $pages->concat($brands)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** robots.txt dinamis agar URL sitemap selalu sesuai domain (APP_URL) */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /invoice/',
            'Disallow: /webhook/',
            'Disallow: /*?q=',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
