<x-layouts.app title="Daftar Harga" description="Daftar harga lengkap top up game, pulsa, paket data, token PLN, dan e-wallet.">
    <x-page-header eyebrow="Harga" title="Daftar Harga" subtitle="Harga diperbarui otomatis mengikuti harga distributor. Harga belum termasuk biaya metode pembayaran dan kode unik.">
        <form method="GET" class="mt-6 grid gap-3 sm:grid-cols-[1fr_220px_auto]">
            <div>
                <label for="q" class="sr-only">Cari produk</label>
                <input id="q" name="q" value="{{ $search }}" class="input" placeholder="Cari produk, mis. Mobile Legends">
            </div>
            <div>
                <label for="kategori" class="sr-only">Kategori</label>
                <select id="kategori" name="kategori" class="input">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $cat)
                        <option @selected($category === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-primary">Tampilkan</button>
        </form>
    </x-page-header>

    <div class="mx-auto max-w-6xl space-y-6 px-4 pt-8">
        <p class="text-text-muted">Menampilkan {{ number_format($total) }} produk</p>

        @forelse ($groups as $key => $products)
            @php([$brandName, $slug] = explode('|', $key))
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
                    <div class="flex items-center gap-3">
                        <x-brand-logo :brand="$products->first()->brand" size="h-10 w-10" />
                        <div>
                            <h2 class="text-base">{{ $brandName }}</h2>
                            <p class="text-xs text-text-muted">{{ $products->first()->category }}</p>
                        </div>
                    </div>
                    <a href="{{ route('brands.show', $slug) }}" class="btn-primary btn-sm">Beli</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Produk</th><th class="text-right">Harga</th></tr></thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr class="hover:bg-slate-50">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <x-product-thumb :product="$product" size="h-8 w-8" />
                                            <span>{{ $product->shortName() }}</span>
                                        </div>
                                    </td>
                                    <td class="text-right font-semibold whitespace-nowrap text-accent-dark">{{ rupiah($product->selling_price) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="card flex flex-col items-center px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-light text-primary"><x-icon name="tag" class="h-7 w-7" /></span>
                <h3 class="mt-4">Produk tidak ditemukan</h3>
                <p class="mt-1 text-text-muted">Coba kata kunci atau kategori lain.</p>
                <a href="{{ route('price-list') }}" class="btn-primary mt-5">Tampilkan semua</a>
            </div>
        @endforelse
    </div>
</x-layouts.app>
