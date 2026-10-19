<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlashSaleController extends Controller
{
    public function index(): View
    {
        return view('admin.flash-sales.index', [
            'flashSales' => FlashSale::query()->with('product.brand')->latest('starts_at')->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.flash-sales.form', [
            'flashSale' => new FlashSale([
                'product_id' => $request->query('product'),
                'is_active' => true,
                'starts_at' => now()->startOfHour(),
                'ends_at' => now()->startOfHour()->addDay(),
            ]),
            'products' => $this->productOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        FlashSale::query()->create($this->validated($request));

        return redirect()->route('admin.flash-sales.index')->with('toast', ['success', '✓ Data berhasil ditambahkan']);
    }

    public function edit(FlashSale $flashSale): View
    {
        return view('admin.flash-sales.form', ['flashSale' => $flashSale, 'products' => $this->productOptions()]);
    }

    public function update(Request $request, FlashSale $flashSale): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['quota'] < $flashSale->sold) {
            return back()->withInput()->withErrors(['quota' => "Kuota tidak boleh kurang dari jumlah terjual ({$flashSale->sold})."]);
        }

        $flashSale->update($data);

        return redirect()->route('admin.flash-sales.index')->with('toast', ['success', '✓ Data berhasil diperbarui']);
    }

    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        // Order lama tetap menyimpan harga yang dibayar; relasinya diset null
        $flashSale->delete();

        return redirect()->route('admin.flash-sales.index')->with('toast', ['success', '✓ Data berhasil dihapus']);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'price' => ['required', 'integer', 'min:1'],
            'quota' => ['required', 'integer', 'min:1', 'max:100000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ], [
            'product_id.required' => 'Pilih produk yang akan diberi flash sale.',
            'ends_at.after' => 'Waktu selesai harus setelah waktu mulai.',
        ]);

        return $data + ['is_active' => $request->boolean('is_active')];
    }

    /** Produk dikelompokkan per brand untuk <optgroup> */
    protected function productOptions()
    {
        return Product::query()->with('brand:id,name,category')
            ->orderBy('category')->orderBy('brand_id')->orderBy('base_price')
            ->get(['id', 'brand_id', 'name', 'category', 'base_price', 'selling_price'])
            ->groupBy(fn (Product $p) => "{$p->brand?->name} ({$p->category})");
    }
}
