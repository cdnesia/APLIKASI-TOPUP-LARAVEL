@php($wa = \App\Models\Setting::get('whatsapp_admin'))
<x-layouts.app :title="'Top Up '.$brand->name" :description="'Top up '.$brand->name.' murah dan cepat di '.\App\Models\Setting::get('site_name')">
    {{-- Header: banner + cover --}}
    <section class="relative">
        <div class="relative h-40 overflow-hidden bg-primary-light dark:bg-ink sm:h-56">
            @if ($brand->bannerUrl())
                <img src="{{ $brand->bannerUrl() }}" alt="" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-bg via-bg/20 to-transparent"></div>
            @else
                <div class="pointer-events-none absolute -top-24 right-0 h-72 w-72 rounded-full bg-primary/25 blur-[110px] dark:bg-primary/40"></div>
                <div class="pointer-events-none absolute -bottom-32 left-1/4 h-64 w-64 rounded-full bg-primary/20 blur-[110px]"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-bg to-transparent"></div>
            @endif
        </div>
        <div class="relative mx-auto -mt-20 flex max-w-6xl items-end gap-4 px-4 sm:-mt-24 sm:gap-6">
            <div class="w-24 shrink-0 overflow-hidden rounded-2xl border-4 border-bg bg-surface shadow-lift sm:w-36">
                @if ($brand->coverUrl())
                    <img src="{{ $brand->coverUrl() }}" alt="{{ $brand->name }}" class="aspect-[3/4] w-full object-cover">
                @else
                    <div class="flex aspect-[3/4] items-center justify-center bg-primary-light/60"><x-brand-logo :brand="$brand" size="h-14 w-14 sm:h-20 sm:w-20" /></div>
                @endif
            </div>
            <div class="min-w-0 pb-1">
                <nav class="mb-1 hidden items-center gap-1.5 text-xs text-text-muted sm:flex" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-text">Beranda</a>
                    <x-icon name="chevron-right" class="h-3 w-3" />
                    <a href="{{ route('home', ['kategori' => $brand->category]) }}#produk" class="hover:text-text">{{ $brand->category }}</a>
                </nav>
                <h1 class="truncate text-2xl sm:text-3xl">{{ $brand->name }}</h1>
                <p class="text-text-muted">{{ $brand->publisher ?: $brand->category }}</p>
            </div>
        </div>
        <div class="mx-auto mt-4 flex max-w-6xl flex-wrap gap-x-5 gap-y-2 px-4 text-xs text-text-muted">
            <span class="inline-flex items-center gap-1.5"><x-icon name="bolt" class="h-4 w-4 text-accent-dark" /> Proses otomatis</span>
            <span class="inline-flex items-center gap-1.5"><x-icon name="shield" class="h-4 w-4 text-success" /> Cukup ID, tanpa password</span>
            <span class="inline-flex items-center gap-1.5"><x-icon name="refresh" class="h-4 w-4 text-primary" /> Gagal? Dana kembali</span>
        </div>
    </section>

    <form action="{{ route('orders.store', $brand) }}" method="POST" id="order-form" class="mx-auto mt-8 grid max-w-6xl items-start gap-6 px-4 lg:grid-cols-[1fr_360px]">
        @csrf
        <div class="space-y-5">
            {{-- 1. Data akun --}}
            <section class="overflow-hidden rounded-2xl border border-border bg-surface">
                <header class="flex items-center gap-3 border-b border-border px-5 py-3.5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-sm font-bold text-white">1</span>
                    <h2 class="text-base">Masukkan Data Akun</h2>
                </header>
                <div class="p-5">
                    <div class="grid gap-4 {{ $brand->zone_label ? 'sm:grid-cols-2' : '' }}">
                        <div>
                            <label for="customer_id" class="label">{{ $brand->field_label }}</label>
                            <input id="customer_id" name="customer_id" value="{{ old('customer_id') }}" required autocomplete="off"
                                class="input @error('customer_id') input-error @enderror" placeholder="Masukkan {{ $brand->field_label }}">
                            @error('customer_id') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        @if ($brand->zone_label)
                            <div>
                                <label for="zone_id" class="label">{{ $brand->zone_label }}</label>
                                <input id="zone_id" name="zone_id" value="{{ old('zone_id') }}" required autocomplete="off"
                                    class="input @error('zone_id') input-error @enderror" placeholder="Masukkan {{ $brand->zone_label }}">
                                @error('zone_id') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                    @if ($brand->input_hint)
                        <p class="hint inline-flex items-center gap-1.5"><x-icon name="book" class="h-3.5 w-3.5" /> {{ $brand->input_hint }}</p>
                    @endif
                </div>
            </section>

            {{-- 2. Nominal --}}
            <section class="overflow-hidden rounded-2xl border border-border bg-surface">
                <header class="flex items-center gap-3 border-b border-border px-5 py-3.5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-sm font-bold text-white">2</span>
                    <h2 class="text-base">Pilih Nominal</h2>
                </header>
                <div class="space-y-6 p-5">
                    @forelse ($groups as $group => $products)
                        @php($isFlash = $group === 'Flash Sale')
                        <div @class(['rounded-2xl border border-accent/40 bg-accent-light/40 p-4' => $isFlash])>
                            @if ($groups->count() > 1 || $isFlash)
                                <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold">
                                    @if ($isFlash) <x-icon name="bolt" class="h-4 w-4 text-accent-dark" /> @endif
                                    {{ $group }}
                                </h3>
                            @endif
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($products as $product)
                                    @php($sale = $product->flashSale)
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="product_id" value="{{ $product->id }}" class="peer sr-only" required
                                            data-price="{{ $product->currentPrice() }}" data-name="{{ $product->shortName() }}" data-thumb="{{ $product->imageUrl() }}"
                                            @checked(old('product_id', $selected) == $product->id)>
                                        <div class="flex h-full flex-col rounded-xl border border-border bg-bg/50 p-3 transition peer-checked:border-primary peer-checked:bg-primary-light peer-checked:ring-2 peer-checked:ring-primary/25 peer-focus-visible:ring-2 hover:border-primary/50">
                                            <p class="text-[13px] leading-snug font-semibold">{{ $product->shortName() }}</p>
                                            <div class="mt-auto flex items-center gap-2 pt-3">
                                                <x-product-thumb :product="$product" size="h-8 w-8" />
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-accent-dark">{{ rupiah($product->currentPrice()) }}</p>
                                                    @if ($sale)
                                                        <p class="text-[11px] text-text-muted line-through">{{ rupiah($product->selling_price) }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($sale)
                                                <div class="mt-2.5 h-1 overflow-hidden rounded-full bg-border"><div class="h-full rounded-full bg-accent" style="width: {{ max(6, $sale->soldPercent()) }}%"></div></div>
                                                <p class="mt-1 text-[10px] text-text-muted">Sisa {{ $sale->remaining() }}</p>
                                            @endif
                                        </div>
                                        <span class="absolute top-2 right-2 hidden h-5 w-5 items-center justify-center rounded-full bg-primary text-white peer-checked:flex">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-border-dark px-4 py-10 text-center">
                            <p class="font-medium">Semua nominal sedang tidak tersedia</p>
                            <p class="mt-1 text-xs text-text-muted">Produk sedang gangguan dari penyedia. Coba lagi nanti.</p>
                            <a href="{{ route('home') }}" class="btn-secondary btn-sm mt-4">Lihat produk lain</a>
                        </div>
                    @endforelse
                    @error('product_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </section>

            {{-- 3. Pembayaran --}}
            <section class="overflow-hidden rounded-2xl border border-border bg-surface">
                <header class="flex items-center gap-3 border-b border-border px-5 py-3.5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-sm font-bold text-white">3</span>
                    <h2 class="text-base">Pilih Pembayaran</h2>
                </header>
                <div class="p-5">
                    @if ($paymentMethods->isEmpty())
                        <div class="rounded-xl border border-dashed border-border-dark px-4 py-8 text-center">
                            <p class="font-medium">Metode pembayaran belum tersedia</p>
                            <p class="mt-1 text-xs text-text-muted">Silakan hubungi admin untuk melakukan pemesanan.</p>
                            <a href="{{ route('contact') }}" class="btn-secondary btn-sm mt-4">Hubungi admin</a>
                        </div>
                    @else
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($paymentMethods as $method)
                                <label class="cursor-pointer">
                                    <input type="radio" name="payment_method_id" value="{{ $method->id }}" data-fee="{{ $method->fee }}" data-min="{{ $method->minAmount() }}" data-name="{{ $method->name }}"
                                        class="peer sr-only" @checked(old('payment_method_id') == $method->id) required>
                                    <div class="flex items-center gap-3 rounded-xl border border-border bg-bg/50 p-3 transition peer-checked:border-primary peer-checked:bg-primary-light peer-checked:ring-2 peer-checked:ring-primary/25 hover:border-primary/50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50 peer-disabled:hover:border-border">
                                        @if ($method->type !== 'qris' && $method->imageUrl())
                                            <img src="{{ $method->imageUrl() }}" alt="" class="h-8 w-12 rounded bg-white object-contain p-0.5">
                                        @else
                                            <span class="flex h-8 w-12 items-center justify-center rounded bg-surface text-[10px] font-bold text-text-muted uppercase">{{ $method->type }}</span>
                                        @endif
                                        <div>
                                            <p class="font-semibold">{{ $method->name }}</p>
                                            <p class="text-xs text-text-muted">{{ $method->fee ? 'Biaya '.rupiah($method->fee) : 'Tanpa biaya admin' }}{{ $method->isAutomatic() ? ' · Otomatis' : '' }}</p>
                                            @if ($method->minAmount())
                                                <p class="hidden text-xs text-warning" data-min-note>Min. total {{ rupiah($method->minAmount()) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method_id') <p class="field-error">{{ $message }}</p> @enderror
                    @endif
                </div>
            </section>

            {{-- 4. Kontak --}}
            <section class="overflow-hidden rounded-2xl border border-border bg-surface">
                <header class="flex items-center gap-3 border-b border-border px-5 py-3.5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-sm font-bold text-white">4</span>
                    <h2 class="text-base">Detail Kontak</h2>
                </header>
                <div class="p-5">
                    <label for="whatsapp" class="label">Nomor WhatsApp</label>
                    <input id="whatsapp" name="whatsapp" type="tel" inputmode="numeric" value="{{ old('whatsapp') }}" required
                        class="input @error('whatsapp') input-error @enderror" placeholder="081234567890">
                    @error('whatsapp') <p class="field-error">{{ $message }}</p> @else <p class="hint">Untuk info status pesanan bila diperlukan.</p> @enderror
                </div>
            </section>

            @if ($brand->description)
                <section class="rounded-2xl border border-border bg-surface p-5">
                    <h2 class="text-base">Tentang {{ $brand->name }}</h2>
                    <p class="mt-2 leading-relaxed whitespace-pre-line text-text-muted">{{ $brand->description }}</p>
                </section>
            @endif
        </div>

        {{-- Ringkasan (sticky) --}}
        <aside class="space-y-4 lg:sticky lg:top-32">
            <section class="rounded-2xl border border-border bg-surface p-5">
                <h2 class="text-base">Ringkasan Pesanan</h2>
                <div id="sum-empty" class="mt-4 rounded-xl border border-dashed border-border-dark px-4 py-6 text-center text-text-muted">Belum ada nominal yang dipilih.</div>
                <div id="sum-item" class="mt-4 hidden items-center gap-3 rounded-xl bg-bg p-3">
                    <img id="sum-thumb" alt="" class="hidden h-10 w-10 rounded-lg object-contain">
                    <span id="sum-thumb-fallback" class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-light text-primary"><x-icon :name="$brand->categoryIcon()" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <p id="sum-name" class="truncate font-semibold"></p>
                        <p class="text-xs text-text-muted">{{ $brand->name }}</p>
                    </div>
                </div>
                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-text-muted">{{ $brand->field_label }}</dt><dd id="sum-id" class="truncate font-medium">—</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-text-muted">Pembayaran</dt><dd id="sum-pay" class="truncate font-medium">—</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-text-muted">Harga</dt><dd id="sum-price" class="font-medium">Rp0</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-text-muted">Biaya admin</dt><dd id="sum-fee" class="font-medium">Rp0</dd></div>
                    <div class="flex items-baseline justify-between gap-3 border-t border-border pt-3"><dt class="font-semibold">Total</dt><dd id="sum-total" class="text-lg font-bold text-accent-dark">Rp0</dd></div>
                </dl>
                <p class="mt-2 text-[11px] text-text-muted">Kode unik 3 digit ditambahkan di invoice untuk verifikasi transfer.</p>
                <button type="submit" class="btn-accent mt-4 hidden w-full py-3 lg:flex" @disabled(! $hasProducts || $paymentMethods->isEmpty())>
                    <x-icon name="bolt" class="h-[18px] w-[18px]" /> Pesan Sekarang
                </button>
            </section>

            <section class="flex items-center gap-3 rounded-2xl border border-border bg-surface p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-light text-primary"><x-icon name="support" class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">Butuh bantuan?</p>
                    <p class="text-xs text-text-muted">Tim CS siap membantu pesananmu.</p>
                </div>
                <a href="{{ $wa ? 'https://wa.me/'.$wa : route('contact') }}" @if ($wa) target="_blank" rel="noopener" @endif class="btn-secondary btn-sm shrink-0">Chat</a>
            </section>
        </aside>

        {{-- Bar ringkasan bawah (HP) --}}
        <div class="fixed inset-x-0 bottom-16 z-30 border-t border-border bg-surface/95 px-4 py-3 backdrop-blur-xl lg:hidden">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3">
                <div class="min-w-0">
                    <p id="bar-name" class="truncate text-xs text-text-muted">Pilih nominal</p>
                    <p id="bar-total" class="text-lg font-bold text-accent-dark">Rp0</p>
                </div>
                <button type="submit" class="btn-accent shrink-0 px-6" @disabled(! $hasProducts || $paymentMethods->isEmpty())>Pesan Sekarang</button>
            </div>
        </div>
    </form>
    {{-- Ruang agar konten tidak tertutup bar ringkasan di HP --}}
    <div class="h-20 lg:hidden"></div>

    @push('scripts')
        <script>
            // Perbarui ringkasan pesanan secara langsung
            (() => {
                const form = document.getElementById('order-form');
                const $ = (id) => document.getElementById(id);
                const fmt = (n) => 'Rp' + n.toLocaleString('id-ID');
                const update = () => {
                    const item = form.querySelector('input[name=product_id]:checked');

                    // Nonaktifkan metode yang minimum totalnya belum terpenuhi (mis. VA min. Rp10.000)
                    form.querySelectorAll('input[name=payment_method_id]').forEach((radio) => {
                        const tooLow = !!item && Number(item.dataset.price) + Number(radio.dataset.fee) < Number(radio.dataset.min);
                        radio.disabled = tooLow;
                        if (tooLow) radio.checked = false;
                        radio.closest('label').querySelector('[data-min-note]')?.classList.toggle('hidden', !tooLow);
                    });

                    const pay = form.querySelector('input[name=payment_method_id]:checked');
                    const price = Number(item?.dataset.price ?? 0), fee = item ? Number(pay?.dataset.fee ?? 0) : 0;
                    const id = [$('customer_id')?.value, $('zone_id')?.value].filter(Boolean);

                    $('sum-empty').classList.toggle('hidden', !!item);
                    $('sum-item').classList.toggle('hidden', !item);
                    $('sum-item').classList.toggle('flex', !!item);
                    if (item) {
                        $('sum-name').textContent = item.dataset.name;
                        $('sum-thumb').src = item.dataset.thumb || '';
                        $('sum-thumb').classList.toggle('hidden', !item.dataset.thumb);
                        $('sum-thumb-fallback').classList.toggle('hidden', !!item.dataset.thumb);
                    }
                    $('sum-id').textContent = id.length ? (id[1] ? `${id[0]} (${id[1]})` : id[0]) : '—';
                    $('sum-pay').textContent = pay?.dataset.name ?? '—';
                    $('sum-price').textContent = fmt(price);
                    $('sum-fee').textContent = fmt(fee);
                    $('sum-total').textContent = $('bar-total').textContent = fmt(price + fee);
                    $('bar-name').textContent = item ? item.dataset.name : 'Pilih nominal';
                };
                form.addEventListener('change', update);
                form.addEventListener('input', update);
                update();
                form.querySelector('input[name=product_id]:checked')?.scrollIntoView({ block: 'center' });

                form.addEventListener('submit', () => form.querySelectorAll('button[type=submit]').forEach((btn) => {
                    btn.disabled = true;
                    btn.textContent = 'Memproses…';
                }));
            })();
        </script>
    @endpush
</x-layouts.app>
