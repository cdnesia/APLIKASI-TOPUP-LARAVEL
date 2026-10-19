<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => Banner::query()->orderBy('sort_order')->latest('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => new Banner(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Banner::query()->create($this->validated($request));

        return redirect()->route('admin.banners.index')->with('toast', ['success', '✓ Data berhasil ditambahkan']);
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', ['banner' => $banner]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $banner->update($this->validated($request, $banner));

        return redirect()->route('admin.banners.index')->with('toast', ['success', '✓ Data berhasil diperbarui']);
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        Storage::disk('public')->delete($banner->image);
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('toast', ['success', '✓ Data berhasil dihapus']);
    }

    protected function validated(Request $request, ?Banner $banner = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'image' => [$banner ? 'nullable' : 'required', 'image', 'max:4096'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'image.required' => 'Gambar banner wajib diunggah.',
            'image.max' => 'Ukuran gambar maksimal 4 MB.',
        ]);

        if ($request->hasFile('image')) {
            if ($banner?->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('banners', 'public');
        } else {
            unset($data['image']);
        }

        return $data + ['sort_order' => $data['sort_order'] ?? 0, 'is_active' => $request->boolean('is_active')];
    }
}
