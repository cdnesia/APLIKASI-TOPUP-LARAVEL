<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4">
        {{-- Banner promo / sapaan --}}
        @if ($banners->isNotEmpty())
            <section class="relative mt-5" aria-roledescription="carousel" aria-label="Promo" id="banner-carousel">
                <div class="flex snap-x snap-mandatory overflow-x-auto rounded-3xl [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-track>
                    @foreach ($banners as $banner)
                        <div class="w-full shrink-0 snap-center" aria-roledescription="slide" aria-label="{{ $loop->iteration }} dari {{ $loop->count }}">
                            @if ($banner->link_url)
                                <a href="{{ $banner->link_url }}"><img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="aspect-[8/3] w-full object-cover" @if (! $loop->first) loading="lazy" @endif></a>
                            @else
                                <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="aspect-[8/3] w-full object-cover" @if (! $loop->first) loading="lazy" @endif>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if ($banners->count() > 1)
                    <button type="button" data-prev class="absolute top-1/2 left-3 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur hover:bg-black/60 sm:flex" aria-label="Banner sebelumnya"><x-icon name="chevron-right" class="h-5 w-5 rotate-180" /></button>
                    <button type="button" data-next class="absolute top-1/2 right-3 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur hover:bg-black/60 sm:flex" aria-label="Banner berikutnya"><x-icon name="chevron-right" class="h-5 w-5" /></button>
                    <div class="mt-3 flex justify-center gap-1.5" data-dots>
                        @foreach ($banners as $banner)
                            <button type="button" class="h-1.5 w-1.5 rounded-full bg-border-dark transition-all" aria-label="Ke banner {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                @endif
            </section>
        @elseif (! $search)
            <section class="relative mt-5 overflow-hidden rounded-3xl bg-ink px-6 py-10 text-white sm:px-10 sm:py-12">
                <div class="pointer-events-none absolute -top-32 -right-20 h-80 w-80 rounded-full bg-primary/40 blur-[110px]"></div>
                <div class="relative max-w-xl">
                    <p class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300"><span class="h-1.5 w-1.5 rounded-full bg-success"></span> Diproses otomatis setelah pembayaran</p>
                    <h1 class="mt-4 text-3xl leading-tight sm:text-4xl">Top up favoritmu, <span class="text-slate-400">tanpa ribet.</span></h1>
                    <p class="mt-3 text-slate-400">Diamond, pulsa, paket data, token listrik, dan saldo e-wallet. Cukup masukkan ID, bayar, selesai.</p>
                    <a href="#produk" class="btn mt-6 bg-white text-ink hover:bg-slate-100">Mulai Top Up</a>
                </div>
            </section>
        @endif

        {{-- Flash sale --}}
        @if ($flashSales->isNotEmpty() && ! $search)
            <section class="mt-10 rounded-3xl border border-accent/30 bg-surface p-4 sm:p-6" aria-labelledby="flash-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent text-white"><x-icon name="bolt" class="h-5 w-5" /></span>
                        <div>
                            <h2 id="flash-title" class="text-lg sm:text-xl">Flash Sale</h2>
                            <p class="text-xs text-text-muted">Harga spesial, stok terbatas.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-sm font-semibold tabular-nums" data-countdown="{{ $flashEndsAt->toIso8601String() }}" aria-label="Sisa waktu flash sale">
                        <span class="text-xs font-medium text-text-muted">Berakhir dalam</span>
                        <span class="rounded-lg bg-text px-2 py-1 text-bg" data-h>00</span>:<span class="rounded-lg bg-text px-2 py-1 text-bg" data-m>00</span>:<span class="rounded-lg bg-text px-2 py-1 text-bg" data-s>00</span>
                    </div>
                </div>
                <div class="-mx-4 mt-5 flex snap-x gap-3 overflow-x-auto px-4 pb-1 sm:-mx-6 sm:px-6 [scrollbar-width:none]">
                    @foreach ($flashSales as $sale)
                        @php($product = $sale->product)
                        <a href="{{ route('brands.show', ['brand' => $product->brand, 'item' => $product->id]) }}" class="group w-56 shrink-0 snap-start rounded-2xl border border-border bg-bg p-3.5 transition hover:border-accent/50">
                            <div class="flex items-center gap-2.5">
                                <x-product-thumb :product="$product" size="h-9 w-9" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ $product->shortName() }}</p>
                                    <p class="truncate text-xs text-text-muted">{{ $product->brand->name }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <p class="text-base font-bold text-accent-dark">{{ rupiah($sale->price) }}</p>
                                <p class="text-xs text-text-muted line-through">{{ rupiah($product->selling_price) }}</p>
                                @if ($disc = $sale->discountPercent($product->selling_price))
                                    <span class="ml-auto rounded-md bg-accent-light px-1.5 py-0.5 text-[10px] font-bold text-accent-dark">-{{ $disc }}%</span>
                                @endif
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-border"><div class="h-full rounded-full bg-accent" style="width: {{ max(6, $sale->soldPercent()) }}%"></div></div>
                            <p class="mt-1.5 text-[11px] text-text-muted">Sisa {{ $sale->remaining() }} dari {{ $sale->quota }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Trending --}}
        @if ($trending->isNotEmpty())
            <section class="mt-10">
                <div class="flex items-center gap-2">
                    <x-icon name="trend" class="h-5 w-5 text-accent" />
                    <h2 class="text-lg sm:text-xl">Trending</h2>
                </div>
                <p class="mt-1 text-text-muted">Produk yang paling sering dibeli saat ini.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($trending as $brand)
                        <a href="{{ route('brands.show', $brand) }}" class="group flex items-center gap-4 rounded-2xl border border-border bg-surface p-3 transition hover:border-primary/40 hover:shadow-lift">
                            @if ($brand->coverUrl())
                                <img src="{{ $brand->coverUrl() }}" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover">
                            @else
                                <x-brand-logo :brand="$brand" size="h-16 w-16" />
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold group-hover:text-primary">{{ $brand->name }}</p>
                                <p class="truncate text-xs text-text-muted">{{ $brand->publisher ?: $brand->category }}</p>
                            </div>
                            <x-icon name="chevron-right" class="mr-1 h-4 w-4 shrink-0 text-text-muted transition group-hover:translate-x-0.5 group-hover:text-primary" />
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Katalog --}}
        <section id="produk" class="mt-12 scroll-mt-32">
            @if ($results !== null)
                <h2 class="text-lg sm:text-xl">Hasil pencarian "{{ $search }}"</h2>
                <p class="mt-1 text-text-muted">{{ $results->count() }} produk ditemukan · <a href="{{ route('home') }}" class="text-primary hover:underline">hapus pencarian</a></p>
                @if ($results->isNotEmpty())
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                        @foreach ($results as $brand)
                            <x-brand-card :brand="$brand" />
                        @endforeach
                    </div>
                @endif
            @endif

            @if ($results === null || $results->isEmpty())
                @if ($results !== null)
                    <div class="mt-5 rounded-2xl border border-dashed border-border-dark px-6 py-10 text-center">
                        <p class="font-medium">Produk tidak ditemukan</p>
                        <p class="mt-1 text-text-muted">Coba kata kunci lain, atau pilih dari katalog di bawah.</p>
                    </div>
                @endif

                @if ($categories->isNotEmpty())
                    <div @class(['mt-10' => $results !== null])>
                        <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]" role="tablist" aria-label="Kategori produk" id="category-tabs">
                            @foreach ($categories as $cat)
                                <button type="button" role="tab" data-tab="{{ $cat }}" aria-selected="{{ $cat === $activeCategory ? 'true' : 'false' }}"
                                    class="inline-flex shrink-0 items-center gap-2 rounded-full border border-border bg-surface px-4 py-2 text-sm font-medium text-text-muted transition hover:text-text aria-selected:border-primary aria-selected:bg-primary aria-selected:text-white">
                                    <x-icon :name="\App\Models\Brand::categoryIconFor($cat)" class="h-4 w-4" /> {{ $cat }}
                                </button>
                            @endforeach
                        </nav>

                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6" id="catalog-grid">
                            @foreach ($brands as $brand)
                                <x-brand-card :brand="$brand" data-category="{{ $brand->category }}" :class="$brand->category !== $activeCategory ? 'hidden' : ''" />
                            @endforeach
                        </div>
                        <div class="mt-6 text-center">
                            <button type="button" id="catalog-more" class="btn-secondary hidden rounded-full px-6">Tampilkan lainnya</button>
                        </div>
                    </div>
                @elseif ($results === null)
                    <div class="flex flex-col items-center rounded-3xl border border-border bg-surface px-6 py-16 text-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-light text-primary"><x-icon name="inbox" class="h-7 w-7" /></span>
                        <h3 class="mt-4">Katalog sedang disiapkan</h3>
                        <p class="mt-1 text-text-muted">Produk akan segera tersedia. Silakan kembali beberapa saat lagi.</p>
                        <a href="{{ route('contact') }}" class="btn-secondary mt-5">Hubungi kami</a>
                    </div>
                @endif
            @endif
        </section>

        {{-- Cara top up --}}
        <section class="mt-20">
            <p class="eyebrow">Cara kerja</p>
            <h2 class="mt-1">Empat langkah, tanpa daftar akun</h2>
            <div class="mt-8">@include('partials.steps')</div>
        </section>

        {{-- FAQ --}}
        <section class="mt-20 grid gap-8 lg:grid-cols-[1fr_1.6fr]">
            <div>
                <p class="eyebrow">FAQ</p>
                <h2 class="mt-1">Pertanyaan yang sering muncul</h2>
                <p class="mt-3 text-text-muted">Belum menemukan jawaban? Tim kami siap membantu.</p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('guide') }}#faq" class="btn-secondary">Semua pertanyaan</a>
                    <a href="{{ route('contact') }}" class="btn-ghost">Hubungi CS</a>
                </div>
            </div>
            <div>@include('partials.faq')</div>
        </section>
    </div>

    @push('scripts')
        <script>
            // Carousel banner: geser otomatis tiap 5 detik, berhenti saat disentuh/hover
            (() => {
                const root = document.getElementById('banner-carousel');
                if (!root) return;
                const track = root.querySelector('[data-track]');
                const dots = [...root.querySelectorAll('[data-dots] button')];
                const count = track.children.length;
                if (count < 2) return;
                const index = () => Math.round(track.scrollLeft / track.clientWidth);
                const go = (i) => track.scrollTo({ left: ((i + count) % count) * track.clientWidth, behavior: 'smooth' });
                const paint = () => dots.forEach((d, i) => {
                    d.classList.toggle('w-5', i === index());
                    d.classList.toggle('bg-primary', i === index());
                });
                root.querySelector('[data-prev]')?.addEventListener('click', () => go(index() - 1));
                root.querySelector('[data-next]')?.addEventListener('click', () => go(index() + 1));
                dots.forEach((d, i) => d.addEventListener('click', () => go(i)));
                track.addEventListener('scroll', paint, { passive: true });
                let timer = setInterval(() => go(index() + 1), 5000);
                ['mouseenter', 'touchstart'].forEach((e) => root.addEventListener(e, () => clearInterval(timer), { passive: true }));
                paint();
            })();

            // Hitung mundur flash sale
            (() => {
                const el = document.querySelector('[data-countdown]');
                if (!el) return;
                const end = new Date(el.dataset.countdown).getTime();
                const pad = (n) => String(n).padStart(2, '0');
                const tick = () => {
                    const left = Math.max(0, end - Date.now());
                    if (left === 0) return location.reload();
                    el.querySelector('[data-h]').textContent = pad(Math.floor(left / 36e5));
                    el.querySelector('[data-m]').textContent = pad(Math.floor(left % 36e5 / 6e4));
                    el.querySelector('[data-s]').textContent = pad(Math.floor(left % 6e4 / 1e3));
                };
                tick();
                setInterval(tick, 1000);
            })();

            // Tab kategori + "Tampilkan lainnya" (maks. 12 kartu per tampilan)
            (() => {
                const tabs = [...document.querySelectorAll('#category-tabs [data-tab]')];
                const cards = [...document.querySelectorAll('#catalog-grid [data-category]')];
                const more = document.getElementById('catalog-more');
                if (!tabs.length) return;
                const LIMIT = 12;
                let current = tabs.find((t) => t.getAttribute('aria-selected') === 'true')?.dataset.tab, expanded = false;
                const render = () => {
                    const inTab = cards.filter((c) => c.dataset.category === current);
                    cards.forEach((c) => c.classList.add('hidden'));
                    inTab.forEach((c, i) => c.classList.toggle('hidden', !expanded && i >= LIMIT));
                    more.classList.toggle('hidden', expanded || inTab.length <= LIMIT);
                    tabs.forEach((t) => t.setAttribute('aria-selected', t.dataset.tab === current));
                };
                tabs.forEach((t) => t.addEventListener('click', () => { current = t.dataset.tab; expanded = false; render(); }));
                more.addEventListener('click', () => { expanded = true; render(); });
                render();
            })();
        </script>
    @endpush
</x-layouts.app>
