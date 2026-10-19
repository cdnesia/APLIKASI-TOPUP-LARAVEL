<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $brands = Brand::query()
            ->withCount('products')
            ->when($request->query('q'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->query('category'), fn ($q, $category) => $q->where('category', $category))
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.brands.index', [
            'brands' => $brands,
            'categories' => Brand::query()->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', ['brand' => $brand]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'publisher' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'field_label' => ['required', 'string', 'max:50'],
            'zone_label' => ['nullable', 'string', 'max:50'],
            'input_hint' => ['nullable', 'string', 'max:150'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'banner' => ['nullable', 'image', 'max:4096'],
            'cover' => ['nullable', 'image', 'max:4096'],
        ]);

        foreach (['image' => 'brands', 'banner' => 'brands/banners', 'cover' => 'brands/covers'] as $field => $folder) {
            $data[$field] = $this->storeImage($request, $field, $folder, $brand->{$field});
        }

        $brand->update($data + [
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'is_popular' => $request->boolean('is_popular'),
        ]);

        return redirect()->route('admin.brands.index')->with('toast', ['success', '✓ Data berhasil diperbarui']);
    }

    /** Simpan/ganti/hapus gambar; mengembalikan path terbaru */
    protected function storeImage(Request $request, string $field, string $folder, ?string $current): ?string
    {
        if ($request->hasFile($field) || $request->boolean("remove_{$field}")) {
            if ($current) {
                Storage::disk('public')->delete($current);
            }

            return $request->hasFile($field) ? $request->file($field)->store($folder, 'public') : null;
        }

        return $current;
    }
}
