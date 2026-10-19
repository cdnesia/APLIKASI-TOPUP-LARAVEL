<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Services\Digiflazz\DigiflazzException;
use App\Services\PricingService;
use App\Services\ProductSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('brand')
            ->when($request->query('q'), fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->when($request->query('brand_id'), fn ($q, $brandId) => $q->where('brand_id', $brandId))
            ->when($request->query('category'), fn ($q, $category) => $q->where('category', $category))
            ->orderBy('category')
            ->orderBy('brand_id')
            ->orderBy('base_price')
            ->paginate(30)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name', 'category']),
            'categories' => Product::query()->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function update(Request $request, Product $product, PricingService $pricing): RedirectResponse
    {
        $data = $request->validate([
            'selling_price' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if (filled($data['selling_price'] ?? null)) {
            if ($data['selling_price'] < $product->base_price) {
                return back()->with('toast', ['warning', '⚠ Harga jual tidak boleh di bawah harga modal ('.rupiah($product->base_price).').']);
            }
            $product->fill(['selling_price' => $data['selling_price'], 'price_locked' => true]);
        } else {
            // Dikosongkan = kembali ke harga otomatis dari margin
            $product->fill(['selling_price' => $pricing->sellingPrice($product->base_price), 'price_locked' => false]);
        }

        $product->is_active = $request->boolean('is_active');
        $product->save();

        return back()->with('toast', ['success', '✓ Data berhasil diperbarui']);
    }

    public function image(Request $request, Product $product): RedirectResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:2048']], [
            'image.image' => 'File harus berupa gambar.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
        ]);

        $old = $product->image;
        $product->update(['image' => $request->file('image')->store('products', 'public')]);
        $this->deleteIfUnused($old);

        return back()->with('toast', ['success', '✓ Data berhasil diperbarui']);
    }

    public function destroyImage(Product $product): RedirectResponse
    {
        $old = $product->image;
        $product->update(['image' => null]);
        $this->deleteIfUnused($old);

        return back()->with('toast', ['success', '✓ Data berhasil dihapus']);
    }

    /** Pakai satu gambar untuk semua nominal dalam satu brand (mis. ikon diamond) */
    public function brandImage(Request $request, Brand $brand): RedirectResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:2048']]);

        $oldImages = $brand->products()->whereNotNull('image')->distinct()->pluck('image');
        $count = $brand->products()->update(['image' => $request->file('image')->store('products', 'public')]);
        $oldImages->each(fn ($old) => $this->deleteIfUnused($old));

        return back()->with('toast', ['success', "✓ Gambar diterapkan ke {$count} nominal {$brand->name}."]);
    }

    public function sync(ProductSyncService $sync): RedirectResponse
    {
        try {
            $stats = $sync->sync();
        } catch (DigiflazzException $e) {
            return back()->with('toast', ['error', '✕ Sinkronisasi gagal: '.$e->getMessage()]);
        }

        return back()->with('toast', ['success', "✓ Sinkronisasi selesai: {$stats['created']} baru, {$stats['updated']} diperbarui, {$stats['deactivated']} nonaktif."]);
    }

    public function reprice(PricingService $pricing): RedirectResponse
    {
        $count = 0;

        Product::query()->where('price_locked', false)->each(function (Product $product) use ($pricing, &$count) {
            $product->update(['selling_price' => $pricing->sellingPrice($product->base_price)]);
            $count++;
        });

        return back()->with('toast', ['success', "✓ Harga {$count} produk dihitung ulang sesuai margin."]);
    }

    /** Satu file bisa dipakai banyak nominal, jadi hanya dihapus bila tak lagi dipakai */
    protected function deleteIfUnused(?string $path): void
    {
        if ($path && ! Product::query()->where('image', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }
}
