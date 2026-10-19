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
            @php($brand = $products->first()->brand)
            <section class="card overflow-hidden" data-price-group>
                <header class="flex items-center justify-between gap-3 border-b border-border px-4 py-3.5 sm:px-5">
                    <a href="{{ route('brands.show', $slug) }}" class="group flex min-w-0 items-center gap-3">
                        <x-brand-logo :brand="$brand" size="h-11 w-11" />
                        <div class="min-w-0">
                            <h2 class="truncate text-base transition group-hover:text-primary">{{ $brandName }}</h2>
                            <p class="text-xs text-text-muted">{{ $brand->category }} · {{ $products->count() }} nominal · mulai <span class="font-semibold text-accent-dark">{{ rupiah($products->min(fn ($p) => $p->currentPrice())) }}</span></p>
                        </div>
                    </a>
                    <a href="{{ route('brands.show', $slug) }}" class="btn-primary btn-sm shrink-0">Top Up</a>
                </header>

                {{-- Grid kartu nominal: klik = buka halaman pesan dengan nominal sudah terpilih --}}
                <ul class="grid grid-cols-2 gap-2.5 p-3 sm:grid-cols-3 sm:p-4 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($products as $product)
                        @php($flash = $product->flashSale)
                        <li @class(['hidden' => $loop->index >= 10]) @if ($loop->index >= 10) data-extra @endif>
                            <a href="{{ route('brands.show', ['brand' => $slug, 'item' => $product->id]) }}"
                                @class([
                                    'group relative flex h-full flex-col gap-2.5 rounded-xl border bg-surface p-3 transition duration-200 hover:-translate-y-0.5 hover:shadow-lift',
                                    'border-accent/50 bg-accent-light/30' => $flash,
                                    'border-border hover:border-primary/40' => ! $flash,
                                ])>
                                @if ($flash)
                                    <span class="absolute -top-2 right-2 inline-flex items-center gap-0.5 rounded-full bg-accent px-2 py-0.5 text-[10px] font-bold text-ink"><x-icon name="bolt" class="h-3 w-3" /> Flash</span>
                                @endif
                                {{-- HP: ikon di atas nama agar nama panjang tidak terpotong --}}
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-2.5">
                                    <x-product-thumb :product="$product" size="h-8 w-8 sm:h-9 sm:w-9" />
                                    <p class="line-clamp-3 min-w-0 text-[13px] leading-snug font-medium" title="{{ $product->name }}">{{ $product->shortName() }}</p>
                                </div>
                                <div class="mt-auto flex items-end justify-between gap-1 border-t border-dashed border-border pt-2">
                                    <div>
                                        @if ($flash)
                                            <p class="text-[11px] text-text-muted line-through">{{ rupiah($product->selling_price) }}</p>
                                        @endif
                                        <p class="text-sm font-bold text-accent-dark">{{ rupiah($product->currentPrice()) }}</p>
                                    </div>
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary-light text-primary opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">→</span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if ($products->count() > 10)
                    <div class="border-t border-border px-4 py-2.5 text-center">
                        <button type="button" class="btn-ghost btn-sm" data-show-more aria-expanded="false">
                            Tampilkan {{ $products->count() - 10 }} nominal lainnya <span aria-hidden="true">↓</span>
                        </button>
                    </div>
                @endif
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

    @push('scripts')
        <script>
            // Brand dengan banyak nominal: tampilkan 10 dulu, sisanya lewat tombol
            document.querySelectorAll('[data-show-more]').forEach((btn) => btn.addEventListener('click', () => {
                const group = btn.closest('[data-price-group]');
                const open = btn.getAttribute('aria-expanded') !== 'true';
                group.querySelectorAll('[data-extra]').forEach((li) => li.classList.toggle('hidden', !open));
                btn.setAttribute('aria-expanded', open);
                btn.innerHTML = open ? 'Sembunyikan <span aria-hidden="true">↑</span>' : btn.dataset.label;
            }));
            document.querySelectorAll('[data-show-more]').forEach((btn) => btn.dataset.label = btn.innerHTML);
        </script>
    @endpush
</x-layouts.app>
