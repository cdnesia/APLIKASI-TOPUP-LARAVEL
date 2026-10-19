<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Support\Faq;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function priceList(Request $request): View
    {
        $category = (string) $request->query('kategori');
        $search = trim((string) $request->query('q'));

        $products = Product::query()
            ->available()
            ->whereHas('brand', fn ($q) => $q->active())
            ->with('brand')
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
            ->orderBy('category')
            ->orderBy('brand_id')
            ->orderBy('selling_price')
            ->get();

        return view('pages.price-list', [
            'groups' => $products->groupBy(fn (Product $p) => $p->brand->name.'|'.$p->brand->slug),
            'categories' => Product::query()->available()->distinct()->orderBy('category')->pluck('category'),
            'category' => $category,
            'search' => $search,
            'total' => $products->count(),
        ]);
    }

    public function guide(): View
    {
        return view('pages.guide', [
            'faqs' => Faq::items(),
            'paymentMethods' => PaymentMethod::query()->active()->get(),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }
}
