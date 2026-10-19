@props(['title' => null, 'description' => null])
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
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    @include('partials.theme-script')
    @include('partials.head')
    <title>{{ $title ? "$title · $siteName" : "$siteName — Top Up Game, Pulsa & E-Wallet" }}</title>
    <meta name="description" content="{{ $description ?? Setting::get('site_tagline') }}">
    <meta name="theme-color" content="#f7f8fb">
</head>
<body class="flex min-h-screen flex-col pb-16 font-sans md:pb-0">
    @if ($announcement = Setting::get('announcement'))
        <div class="bg-ink px-4 py-2 text-center text-xs text-slate-300">{{ $announcement }}</div>
    @endif

    <header id="site-header" class="sticky top-0 z-40 border-b border-border bg-bg/85 backdrop-blur-xl">
        {{-- Baris 1: logo, pencarian, tema --}}
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4 sm:gap-5">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ $siteName }} — beranda"><x-logo /></a>

            <form action="{{ route('home') }}#produk" method="GET" role="search" id="header-search"
                class="absolute inset-x-0 top-16 hidden border-b border-border bg-bg px-4 py-3 sm:static sm:block sm:flex-1 sm:border-0 sm:bg-transparent sm:p-0">
                <label for="header-q" class="sr-only">Cari game atau produk</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 h-[18px] w-[18px] -translate-y-1/2 text-text-muted" />
                    <input id="header-q" name="q" type="search" value="{{ $search }}" placeholder="Cari game, pulsa, atau e-wallet…"
                        class="h-10 w-full rounded-xl border border-border bg-surface pr-4 pl-10 text-sm text-text placeholder:text-text-muted/70 focus:border-primary focus:ring-4 focus:ring-primary/10 focus:outline-none">
                </div>
            </form>

            <div class="ml-auto flex items-center gap-2 sm:ml-0">
                <button type="button" id="search-toggle" class="flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-surface text-text-muted sm:hidden" aria-label="Buka pencarian" aria-controls="header-search" aria-expanded="false">
                    <x-icon name="search" class="h-[18px] w-[18px]" />
                </button>
                <x-theme-toggle />
                @if ($wa)
                    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn-primary hidden h-10 rounded-xl lg:inline-flex"><x-icon name="chat" class="h-[18px] w-[18px]" /> Chat CS</a>
                @endif
            </div>
        </div>

        {{-- Baris 2: menu (desktop) --}}
        <nav class="hidden border-t border-border/70 md:block" aria-label="Menu utama">
            <div class="mx-auto flex h-11 max-w-6xl items-center justify-between px-4">
                <ul class="flex h-full items-center gap-7">
                    @foreach ($nav as [$route, $label, $icon])
                        @php($active = request()->routeIs($route) || ($route === 'home' && request()->routeIs('brands.show')))
                        <li class="h-full">
                            <a href="{{ route($route) }}" @class([
                                'relative flex h-full items-center gap-2 text-sm font-medium transition-colors',
                                'text-primary' => $active,
                                'text-text-muted hover:text-text' => ! $active,
                            ]) @if ($active) aria-current="page" @endif>
                                <x-icon :name="$icon" class="h-[18px] w-[18px]" /> {{ $label }}
                                @if ($active)
                                    <span class="absolute inset-x-0 bottom-0 h-0.5 rounded-full bg-primary"></span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                    <li class="h-full">
                        <a href="{{ route('contact') }}" @class(['flex h-full items-center gap-2 text-sm font-medium transition-colors', 'text-primary' => request()->routeIs('contact'), 'text-text-muted hover:text-text' => ! request()->routeIs('contact')])>
                            <x-icon name="chat" class="h-[18px] w-[18px]" /> Kontak
                        </a>
                    </li>
                </ul>
                @if ($hours = Setting::get('operational_hours'))
                    <p class="hidden items-center gap-2 text-xs text-text-muted lg:flex"><span class="h-1.5 w-1.5 rounded-full bg-success"></span> CS: {{ $hours }}</p>
                @endif
            </div>
        </nav>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-20 border-t border-border bg-surface">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
            <div>
                <x-logo />
                <p class="mt-4 max-w-xs leading-relaxed text-text-muted">{{ Setting::get('site_tagline') }}</p>
                @if ($hours = Setting::get('operational_hours'))
                    <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-bg px-3 py-1.5 text-xs text-text-muted">
                        <span class="h-1.5 w-1.5 rounded-full bg-success"></span> {{ $hours }}
                    </p>
                @endif
            </div>
            <div>
                <h4 class="mb-4 text-xs font-semibold tracking-wider text-text-muted uppercase">Jelajahi</h4>
                <ul class="space-y-2.5">
                    @foreach ($nav as [$route, $label])
                        <li><a href="{{ route($route) }}" class="hover:text-primary">{{ $label }}</a></li>
                    @endforeach
                    <li><a href="{{ route('contact') }}" class="hover:text-primary">Kontak</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 text-xs font-semibold tracking-wider text-text-muted uppercase">Kategori</h4>
                <ul class="space-y-2.5">
                    @forelse ($footerCategories as $category)
                        <li><a href="{{ route('home', ['kategori' => $category]) }}#produk" class="hover:text-primary">{{ $category }}</a></li>
                    @empty
                        <li class="text-text-muted">Segera hadir</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h4 class="mb-4 text-xs font-semibold tracking-wider text-text-muted uppercase">Butuh Bantuan?</h4>
                <ul class="space-y-2.5">
                    @if ($wa)
                        <li><a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 hover:text-primary"><x-icon name="phone" class="h-4 w-4 text-text-muted" /> WhatsApp CS</a></li>
                    @endif
                    @if ($email = Setting::get('contact_email'))
                        <li><a href="mailto:{{ $email }}" class="inline-flex items-center gap-2 break-all hover:text-primary"><x-icon name="mail" class="h-4 w-4 shrink-0 text-text-muted" /> {{ $email }}</a></li>
                    @endif
                    <li><a href="{{ route('contact') }}" class="inline-flex items-center gap-2 hover:text-primary"><x-icon name="chat" class="h-4 w-4 text-text-muted" /> Hubungi kami</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-border">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-xs text-text-muted sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} {{ $siteName }}. Nama & logo game milik pemegang merek masing-masing.</p>
                <p class="flex gap-4">
                    <a href="{{ route('terms') }}" class="hover:text-text">Syarat & Ketentuan</a>
                    <a href="{{ route('privacy') }}" class="hover:text-text">Kebijakan Privasi</a>
                </p>
            </div>
        </div>
    </footer>

    {{-- Bar navigasi bawah (HP) --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-surface/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl md:hidden" aria-label="Navigasi bawah">
        <ul class="grid grid-cols-4">
            @foreach ($nav as [$route, $label, $icon, $short])
                @php($active = request()->routeIs($route))
                <li>
                    <a href="{{ route($route) }}" @class([
                        'flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium transition-colors',
                        'text-primary' => $active,
                        'text-text-muted' => ! $active,
                    ]) @if ($active) aria-current="page" @endif>
                        <x-icon :name="$icon" class="h-[22px] w-[22px]" />
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
        })();
    </script>
    @stack('scripts')
</body>
</html>
