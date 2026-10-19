@props(['title' => null, 'description' => null, 'image' => null, 'noindex' => false, 'schema' => []])
@php
    use App\Models\Setting;
    $siteName = Setting::get('site_name');
    $wa = Setting::get('whatsapp_admin');
    // [route, label, ikon, label pendek untuk bar bawah HP]
    $nav = [
        ['home', 'Top Up', 'bolt', 'Top Up'],
        ['track', 'Cek Pesanan', 'receipt', 'Pesanan'],
        ['price-list', 'Daftar Harga', 'tag', 'Harga'],
        ['guide', 'Bantuan', 'book', 'Bantuan'],
    ];
    $search = request()->routeIs('home') ? request('q') : null;
    $footerCategories = \App\Models\Brand::query()->active()->whereHas('products', fn ($q) => $q->available())
        ->distinct()->orderBy('category')->limit(6)->pluck('category');
    $footerPayments = \App\Models\PaymentMethod::query()->active()->pluck('name');
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    @include('partials.theme-script')
    @include('partials.head')
    @php($pageTitle = $title ? "$title · $siteName" : (Setting::get('seo_home_title') ?: \App\Support\Seo::defaultHomeTitle()))
    <title>{{ $pageTitle }}</title>
    @include('partials.seo', ['metaDescription' => $description ?? Setting::get('site_tagline'), 'image' => $image, 'noindex' => $noindex, 'schema' => $schema])
    <meta name="theme-color" content="#f5f7fb">
</head>
<body class="flex min-h-screen flex-col pb-16 font-sans md:pb-0">
    {{-- Bar info gelap: senada dengan footer --}}
    <div class="bg-ink text-xs text-slate-400">
        <div class="mx-auto flex h-9 max-w-6xl items-center justify-between gap-4 px-4">
            @if ($announcement = Setting::get('announcement'))
                <p class="flex min-w-0 items-center gap-2 text-slate-200"><span class="rounded-md bg-accent px-1.5 py-0.5 text-[10px] font-bold text-ink uppercase">Info</span><span class="truncate">{{ $announcement }}</span></p>
            @else
                <p class="flex min-w-0 items-center gap-2"><x-icon name="bolt" class="h-3.5 w-3.5 shrink-0 text-accent" /><span class="truncate">Top up instan, diproses otomatis 24 jam</span></p>
            @endif
            <div class="hidden shrink-0 items-center gap-5 md:flex">
                @if ($hours = Setting::get('operational_hours'))
                    <span class="flex items-center gap-1.5"><span class="relative flex h-1.5 w-1.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-60"></span><span class="relative h-1.5 w-1.5 rounded-full bg-success"></span></span> CS {{ $hours }}</span>
                @endif
                @if ($wa)
                    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="flex items-center gap-1.5 transition hover:text-white"><x-icon name="phone" class="h-3.5 w-3.5" /> +{{ $wa }}</a>
                @endif
                <a href="{{ route('guide') }}#faq" class="transition hover:text-white">FAQ</a>
            </div>
        </div>
    </div>

    <header id="site-header" class="sticky top-0 z-40 border-b border-border bg-surface/90 backdrop-blur-xl transition-shadow">
        {{-- Baris 1: logo, pencarian, aksi --}}
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4 sm:gap-6">
            <a href="{{ route('home') }}" class="shrink-0 transition hover:opacity-80" aria-label="{{ $siteName }} — beranda"><x-logo /></a>

            <form action="{{ route('home') }}#produk" method="GET" role="search" id="header-search"
                class="absolute inset-x-0 top-16 hidden border-b border-border bg-surface px-4 py-3 shadow-lg sm:static sm:block sm:max-w-xl sm:flex-1 sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
                <label for="header-q" class="sr-only">Cari game atau produk</label>
                <div class="group relative">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 h-[18px] w-[18px] -translate-y-1/2 text-text-muted transition group-focus-within:text-primary" />
                    <input id="header-q" name="q" type="search" value="{{ $search }}" placeholder="Cari Mobile Legends, Free Fire, pulsa…"
                        class="h-11 w-full rounded-xl border border-border bg-bg pr-12 pl-10 text-sm text-text transition placeholder:text-text-muted/70 focus:border-primary focus:bg-surface focus:ring-4 focus:ring-primary/10 focus:outline-none">
                    <kbd class="pointer-events-none absolute top-1/2 right-3 hidden -translate-y-1/2 rounded-md border border-border bg-surface px-1.5 py-0.5 text-[11px] font-semibold text-text-muted lg:block">/</kbd>
                </div>
            </form>

            <div class="ml-auto flex items-center gap-2">
                <button type="button" id="search-toggle" class="flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-surface text-text-muted transition hover:text-text sm:hidden" aria-label="Buka pencarian" aria-controls="header-search" aria-expanded="false">
                    <x-icon name="search" class="h-[18px] w-[18px]" />
                </button>
                <x-theme-toggle />
                <a href="{{ route('track') }}" class="btn-secondary hidden h-10 lg:inline-flex"><x-icon name="receipt" class="h-[18px] w-[18px]" /> Cek Pesanan</a>
                @if ($wa)
                    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn-primary hidden h-10 md:inline-flex"><x-icon name="chat" class="h-[18px] w-[18px]" /> Chat CS</a>
                @endif
            </div>
        </div>

        {{-- Baris 2: menu (desktop) --}}
        <nav class="hidden border-t border-border/70 md:block" aria-label="Menu utama">
            <ul class="mx-auto flex h-12 max-w-6xl items-center gap-1 px-4">
                @foreach (array_merge($nav, [['contact', 'Kontak', 'chat']]) as [$route, $label, $icon])
                    @php($active = request()->routeIs($route) || ($route === 'home' && request()->routeIs('brands.show')))
                    <li>
                        <a href="{{ route($route) }}" @class([
                            'flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                            'bg-primary-light text-primary' => $active,
                            'text-text-muted hover:bg-bg hover:text-text' => ! $active,
                        ]) @if ($active) aria-current="page" @endif>
                            <x-icon :name="$icon" class="h-[18px] w-[18px]" /> {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-24 bg-ink text-slate-300">
        <div class="mx-auto max-w-6xl px-4">
            {{-- Ajakan bantuan: kartu menonjol yang "menggantung" di tepi atas footer --}}
            <section class="relative -top-10 flex flex-col gap-5 rounded-2xl bg-primary p-6 text-white shadow-xl shadow-primary/25 sm:p-8 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15"><x-icon name="support" class="h-6 w-6" /></span>
                    <div>
                        <h2 class="text-lg text-white sm:text-xl">Ada kendala dengan pesanan?</h2>
                        <p class="mt-1 text-sm text-white/80">Tim CS kami siap bantu cek status & proses ulang. Siapkan nomor invoice Anda.</p>
                    </div>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row md:shrink-0">
                    @if ($wa)
                        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn bg-white text-primary hover:bg-white/90"><x-icon name="chat" class="h-4 w-4" /> Chat WhatsApp</a>
                    @endif
                    <a href="{{ route('track') }}" class="btn border border-white/30 text-white hover:bg-white/10"><x-icon name="receipt" class="h-4 w-4" /> Cek Pesanan</a>
                </div>
            </section>

            <div class="grid gap-10 pb-12 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.3fr]">
                <div>
                    <x-logo dark />
                    <p class="mt-4 max-w-xs leading-relaxed text-slate-400">{{ Setting::get('site_tagline') }}</p>
                    <ul class="mt-5 space-y-2.5 text-sm">
                        @foreach ([['bolt', 'Proses otomatis 24 jam'], ['shield', 'Pembayaran aman & terverifikasi'], ['clock', $hours = Setting::get('operational_hours')]] as [$icon, $text])
                            @if ($text)
                                <li class="flex items-center gap-2.5">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/5 text-accent"><x-icon :name="$icon" class="h-4 w-4" /></span>
                                    {{ $text }}
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>

                {{-- Dua kolom tautan berdampingan di HP agar footer tidak terlalu panjang --}}
                <div class="grid grid-cols-2 gap-8 sm:col-span-1 lg:col-span-2">
                    <nav aria-label="Jelajahi">
                        <h3 class="mb-4 text-xs font-semibold tracking-wider text-slate-500 uppercase">Jelajahi</h3>
                        <ul class="space-y-2.5">
                            @foreach ($nav as [$route, $label])
                                <li><a href="{{ route($route) }}" class="group inline-flex items-center gap-1 transition hover:text-white">{{ $label }}<span class="-translate-x-1 opacity-0 transition group-hover:translate-x-0 group-hover:opacity-100">→</span></a></li>
                            @endforeach
                            <li><a href="{{ route('contact') }}" class="group inline-flex items-center gap-1 transition hover:text-white">Kontak<span class="-translate-x-1 opacity-0 transition group-hover:translate-x-0 group-hover:opacity-100">→</span></a></li>
                        </ul>
                    </nav>
                    <nav aria-label="Kategori">
                        <h3 class="mb-4 text-xs font-semibold tracking-wider text-slate-500 uppercase">Kategori</h3>
                        <ul class="space-y-2.5">
                            @forelse ($footerCategories as $category)
                                <li><a href="{{ route('home', ['kategori' => $category]) }}#produk" class="group inline-flex items-center gap-1 transition hover:text-white">{{ $category }}<span class="-translate-x-1 opacity-0 transition group-hover:translate-x-0 group-hover:opacity-100">→</span></a></li>
                            @empty
                                <li class="text-slate-500">Segera hadir</li>
                            @endforelse
                        </ul>
                    </nav>
                </div>

                <div class="sm:col-span-2 lg:col-span-1">
                    <h3 class="mb-4 text-xs font-semibold tracking-wider text-slate-500 uppercase">Hubungi Kami</h3>
                    <ul class="space-y-3">
                        @if ($wa)
                            <li>
                                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 p-3 transition hover:border-green-500/50 hover:bg-white/10">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-500/15 text-green-400"><x-icon name="phone" class="h-4 w-4" /></span>
                                    <span><span class="block text-xs text-slate-500">WhatsApp CS</span><span class="font-semibold text-white">+{{ $wa }}</span></span>
                                </a>
                            </li>
                        @endif
                        @if ($email = Setting::get('contact_email'))
                            <li>
                                <a href="mailto:{{ $email }}" class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 p-3 transition hover:border-primary/60 hover:bg-white/10">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/20 text-blue-300"><x-icon name="mail" class="h-4 w-4" /></span>
                                    <span class="min-w-0"><span class="block text-xs text-slate-500">Email</span><span class="block truncate font-semibold text-white">{{ $email }}</span></span>
                                </a>
                            </li>
                        @endif
                        @if (! $wa && ! $email)
                            <li><a href="{{ route('contact') }}" class="hover:text-white">Lihat halaman kontak →</a></li>
                        @endif
                    </ul>
                </div>
            </div>

            @if ($footerPayments->isNotEmpty())
                <div class="flex flex-col gap-3 border-t border-white/10 py-6 sm:flex-row sm:items-center">
                    <p class="shrink-0 text-xs font-semibold tracking-wider text-slate-500 uppercase">Metode Pembayaran</p>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($footerPayments as $method)
                            <li class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-200">{{ $method }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-col gap-3 border-t border-white/10 py-6 text-xs text-slate-500 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1">
                    <p>&copy; {{ date('Y') }} <span class="font-semibold text-slate-300">{{ $siteName }}</span>. Seluruh hak cipta dilindungi.</p>
                    <p>Merek dagang game milik pemiliknya masing-masing; {{ $siteName }} bukan afiliasi resmi penerbit game.</p>
                </div>
                <nav class="flex flex-wrap items-center gap-x-2 gap-y-1 md:shrink-0" aria-label="Tautan legal">
                    <a href="{{ route('terms') }}" class="transition hover:text-white">Syarat & Ketentuan</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('privacy') }}" class="transition hover:text-white">Kebijakan Privasi</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('contact') }}" class="transition hover:text-white">Kontak</a>
                </nav>
            </div>
        </div>
    </footer>

    {{-- Bar navigasi bawah (HP) --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-surface/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl md:hidden" aria-label="Navigasi bawah">
        <ul class="grid grid-cols-4">
            @foreach ($nav as [$route, $label, $icon, $short])
                @php($active = request()->routeIs($route) || ($route === 'home' && request()->routeIs('brands.show')))
                <li>
                    <a href="{{ route($route) }}" @class([
                        'flex flex-col items-center gap-1 py-2 text-[11px] font-medium transition-colors',
                        'text-primary' => $active,
                        'text-text-muted' => ! $active,
                    ]) @if ($active) aria-current="page" @endif>
                        <span @class(['flex h-7 w-12 items-center justify-center rounded-full transition', 'bg-primary-light' => $active])><x-icon :name="$icon" class="h-[22px] w-[22px]" /></span>
                        {{ $short }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    @include('partials.toast')
    <script>
        // Buka/tutup kolom pencarian di HP
        (() => {
            const btn = document.getElementById('search-toggle');
            const form = document.getElementById('header-search');
            btn.addEventListener('click', () => {
                const open = form.classList.toggle('hidden') === false;
                btn.setAttribute('aria-expanded', open);
                if (open) form.querySelector('input').focus();
            });

            // Tekan "/" untuk langsung ke kolom pencarian
            document.addEventListener('keydown', (e) => {
                if (e.key !== '/' || ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
                e.preventDefault();
                form.classList.remove('hidden');
                form.querySelector('input').focus();
            });

            // Bayangan tipis saat halaman digulir
            const header = document.getElementById('site-header');
            const onScroll = () => header.classList.toggle('shadow-md', window.scrollY > 8);
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        })();
    </script>
    @stack('scripts')
</body>
</html>
