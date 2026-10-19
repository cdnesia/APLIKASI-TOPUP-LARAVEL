<x-layouts.admin title="Produk & Harga">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-text-muted">Produk ditarik dari Digiflazz. Harga jual otomatis = modal + margin (atur di <a href="{{ route('admin.settings.edit') }}" class="text-primary hover:underline">Pengaturan</a>). Klik thumbnail untuk mengganti gambar nominal.</p>
        <div class="flex shrink-0 gap-2">
            <form action="{{ route('admin.products.reprice') }}" method="POST" data-confirm="Hitung ulang harga jual semua produk (kecuali yang harganya diatur manual)?">
                @csrf<button class="btn-secondary btn-sm">Hitung Ulang Harga</button>
            </form>
            <form action="{{ route('admin.products.sync') }}" method="POST" id="sync-form">
                @csrf<button class="btn-primary btn-sm">Sinkron Digiflazz</button>
            </form>
        </div>
    </div>

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="q" class="label">Cari</label>
            <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Nama produk atau SKU">
        </div>
        <div>
            <label for="category" class="label">Kategori</label>
            <select id="category" name="category" class="input">
                <option value="">Semua</option>
                @foreach ($categories as $category)
                    <option @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="brand_id" class="label">Brand</label>
            <select id="brand_id" name="brand_id" class="input">
                <option value="">Semua</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }} ({{ $brand->category }})</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-4">
            <button class="btn-primary">Terapkan Filter</button>
            <a href="{{ route('admin.products.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="min-w-[180px]">Produk</th>
                        <th>Modal</th>
                        <th class="min-w-[220px]">Harga Jual</th>
                        <th>Laba</th>
                        <th>Status Digiflazz</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td>
                                <div class="flex items-start gap-3">
                                    <div class="shrink-0 text-center">
                                        <form action="{{ route('admin.products.image', $product) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <label class="group relative block cursor-pointer" title="Klik untuk mengganti gambar">
                                                <x-product-thumb :product="$product" size="h-12 w-12" />
                                                <span class="absolute inset-0 flex items-center justify-center rounded-lg bg-text/60 text-white opacity-0 transition group-hover:opacity-100"><x-icon name="photo" class="h-5 w-5" /></span>
                                                <input type="file" name="image" accept="image/*" class="sr-only" onchange="this.form.submit()">
                                                <span class="sr-only">Unggah gambar {{ $product->name }}</span>
                                            </label>
                                        </form>
                                        @if ($product->image)
                                            <form action="{{ route('admin.products.image.destroy', $product) }}" method="POST" data-confirm="Hapus gambar nominal ini? Tampilan akan kembali memakai logo brand.">
                                                @csrf @method('DELETE')
                                                <button class="mt-1 text-[11px] text-danger hover:underline">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ $product->name }}</p>
                                        <p class="text-xs text-text-muted">{{ $product->brand?->name }} · {{ $product->sku }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">{{ rupiah($product->base_price) }}</td>
                            <td>
                                <form action="{{ route('admin.products.update', $product) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                    @csrf @method('PUT')
                                    <label for="price-{{ $product->id }}" class="sr-only">Harga jual {{ $product->name }}</label>
                                    <input id="price-{{ $product->id }}" name="selling_price" type="number" min="{{ $product->base_price }}"
                                        value="{{ $product->price_locked ? $product->selling_price : '' }}" placeholder="{{ $product->selling_price }} (otomatis)"
                                        class="input w-36 py-1.5">
                                    <label class="flex items-center gap-1 text-xs">
                                        <input type="checkbox" name="is_active" value="1" @checked($product->is_active) class="rounded border-border-dark text-primary"> Jual
                                    </label>
                                    <button class="btn-secondary btn-sm">Simpan</button>
                                    <a href="{{ route('admin.flash-sales.create', ['product' => $product->id]) }}" class="btn-ghost btn-sm" title="Buat flash sale untuk produk ini"><x-icon name="bolt" class="h-4 w-4" /></a>
                                </form>
                                <p class="mt-1 text-xs text-text-muted">{{ $product->price_locked ? 'Harga manual — kosongkan untuk otomatis' : 'Otomatis dari margin' }}</p>
                            </td>
                            <td class="whitespace-nowrap font-medium text-success">{{ rupiah($product->profit()) }}</td>
                            <td>
                                @if ($product->provider_active && ($product->unlimited_stock || $product->stock > 0))
                                    <span class="badge bg-green-100 text-green-800">Tersedia</span>
                                @else
                                    <span class="badge bg-red-100 text-red-700">Gangguan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <p class="font-medium">Belum ada produk</p>
                                <p class="mt-1 text-xs text-text-muted">Klik "Sinkron Digiflazz" untuk menarik daftar produk dan harga.</p>
                                <button form="sync-form" class="btn-primary btn-sm mt-3">Sinkron Sekarang</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="px-4 py-3">{{ $products->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
