<x-layouts.admin title="Brand">
    <p class="mb-4 text-text-muted">Brand dibuat otomatis saat sinkronisasi. Lengkapi logo, deskripsi, dan label input agar halaman order lebih jelas.</p>

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-3">
        <div>
            <label for="q" class="label">Cari</label>
            <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Nama brand">
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
        <div class="flex items-end gap-2">
            <button class="btn-primary">Terapkan</button>
            <a href="{{ route('admin.brands.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Brand</th><th>Kategori</th><th>Input</th><th>Produk</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr class="hover:bg-slate-50">
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-brand-logo :brand="$brand" size="h-10 w-10" />
                                    <span class="font-medium">{{ $brand->name }}</span>
                                </div>
                            </td>
                            <td>{{ $brand->category }}</td>
                            <td class="text-xs">{{ $brand->field_label }}{{ $brand->zone_label ? ' + '.$brand->zone_label : '' }}</td>
                            <td>{{ $brand->products_count }}</td>
                            <td class="space-x-1 whitespace-nowrap">
                                <span class="badge {{ $brand->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">{{ $brand->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                @if ($brand->is_popular) <span class="badge bg-accent-light text-accent-dark">Populer</span> @endif
                            </td>
                            <td class="text-right"><a href="{{ route('admin.brands.edit', $brand->id) }}" class="btn-secondary btn-sm">Edit</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <p class="font-medium">Belum ada brand</p>
                                <p class="mt-1 text-xs text-text-muted">Brand muncul otomatis setelah sinkronisasi produk.</p>
                                <a href="{{ route('admin.products.index') }}" class="btn-primary btn-sm mt-3">Ke Halaman Produk</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($brands->hasPages())
            <div class="px-4 py-3">{{ $brands->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
