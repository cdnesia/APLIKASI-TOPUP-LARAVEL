<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\FlashSale;
use App\Support\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Kategori terlaris ditampilkan lebih dulu, sisanya urut abjad */
    protected const CATEGORY_PRIORITY = ['Games', 'Pulsa', 'Data', 'E-Money', 'PLN', 'Voucher'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $brands = Brand::query()
            ->active()
            ->whereHas('products', fn ($q) => $q->available())
            ->orderByDesc('is_popular')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = $this->sortCategories($brands->pluck('category')->unique());
        $category = (string) $request->query('kategori');

        // Trending: brand yang ditandai populer, atau game pertama bila belum ada
        $trending = $brands->where('is_popular', true);
        if ($trending->isEmpty()) {
            $trending = $brands->where('category', 'Games');
        }

        $flashSales = FlashSale::query()->running()
            ->whereHas('product', fn ($q) => $q->available()->whereHas('brand', fn ($q) => $q->active()))
            ->with('product.brand')
            ->orderBy('ends_at')
            ->limit(12)
            ->get();

        return view('home', [
            'search' => $search,
            'banners' => Banner::query()->active()->get(),
            'flashSales' => $flashSales,
            'flashEndsAt' => $flashSales->min('ends_at'),
            'trending' => $search ? collect() : $trending->take(6),
            'results' => $search ? $brands->filter(fn (Brand $b) => str_contains(strtolower($b->name.' '.$b->publisher), strtolower($search)))->values() : null,
            'categories' => $categories,
            'activeCategory' => $categories->contains($category) ? $category : $categories->first(),
            'brands' => $brands->sortBy(fn (Brand $b) => $categories->search($b->category))->values(),
            'faqs' => array_slice(Faq::items(), 0, 4),
        ]);
    }

    protected function sortCategories(Collection $categories): Collection
    {
        return $categories->sortBy(function (string $category) {
            $rank = array_search($category, self::CATEGORY_PRIORITY);

            return ($rank === false ? 99 : $rank).$category;
        })->values();
    }
}
