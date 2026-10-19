@props(['title' => 'Dashboard', 'subtitle' => null])
@php
    use App\Enums\PaymentStatus;
    use App\Models\Order;

    $pending = Order::query()->where('payment_status', PaymentStatus::WaitingConfirmation)->count();
    $confirmFilter = request()->routeIs('admin.orders.index') && request('payment_status') === PaymentStatus::WaitingConfirmation->value;

    // [label, url, ikon, aktif?, badge]
    // Badge merah kecil di menu Log API bila ada panggilan gagal hari ini
    $failedApiToday = \App\Models\ApiLog::query()->where('success', false)->where('created_at', '>=', today())->count();
    $menu = [
        'Utama' => [
            ['Dashboard', route('admin.dashboard'), 'squares', request()->routeIs('admin.dashboard'), null],
        ],
        'Transaksi' => [
            ['Semua Transaksi', route('admin.orders.index'), 'receipt', request()->routeIs('admin.orders.*') && ! $confirmFilter, null],
            ['Perlu Konfirmasi', route('admin.orders.index', ['payment_status' => PaymentStatus::WaitingConfirmation->value]), 'clock', $confirmFilter, $pending ?: null],
        ],
        'Katalog' => [
            ['Produk & Harga', route('admin.products.index'), 'cube', request()->routeIs('admin.products.*'), null],
            ['Brand', route('admin.brands.index'), 'tag', request()->routeIs('admin.brands.*'), null],
        ],
        'Pemasaran' => [
            ['Flash Sale', route('admin.flash-sales.index'), 'bolt', request()->routeIs('admin.flash-sales.*'), null],
            ['Banner Promo', route('admin.banners.index'), 'photo', request()->routeIs('admin.banners.*'), null],
        ],
        'Keuangan' => [
            ['Laporan Laba', route('admin.reports'), 'chart', request()->routeIs('admin.reports'), null],
            ['Metode Pembayaran', route('admin.payment-methods.index'), 'card', request()->routeIs('admin.payment-methods.*'), null],
        ],
        'Sistem' => [
            ['Pengaturan', route('admin.settings.edit'), 'cog', request()->routeIs('admin.settings.*'), null],
            ['Log API', route('admin.api-logs.index'), 'document', request()->routeIs('admin.api-logs.*'), $failedApiToday ?: null],
            ['Profil Saya', route('admin.profile.edit'), 'user', request()->routeIs('admin.profile.*'), null],
        ],
    ];
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
    <title>{{ $title }} · Admin {{ \App\Models\Setting::get('site_name') }}</title>
</head>
<body class="font-sans">
    <div class="lg:flex">
        {{-- Sidebar --}}
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-ink text-slate-300 transition-transform lg:sticky lg:top-0 lg:h-screen lg:shrink-0 lg:translate-x-0">
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-white/10 px-5">
                <x-logo dark />
                <span class="rounded-md bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold tracking-wider text-slate-300 uppercase">Admin</span>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Menu admin">
                @foreach ($menu as $group => $items)
                    <div>
                        <p class="mb-2 px-3 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{{ $group }}</p>
                        <ul class="space-y-0.5">
                            @foreach ($items as [$label, $url, $icon, $active, $badge])
                                <li>
                                    <a href="{{ $url }}" @class([
                                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                        'bg-white/10 text-white' => $active,
                                        'hover:bg-white/5 hover:text-white' => ! $active,
                                    ]) @if ($active) aria-current="page" @endif>
                                        <x-icon :name="$icon" :class="'h-5 w-5 shrink-0 '.($active ? 'text-primary-light' : 'text-slate-500')" />
                                        <span class="flex-1">{{ $label }}</span>
                                        @if ($badge)
                                            <span class="rounded-full bg-accent px-2 py-0.5 text-[11px] font-semibold text-ink">{{ $badge }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>

            <div class="shrink-0 border-t border-white/10 p-3">
                <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-white/5 hover:text-white">
                    <x-icon name="external" class="h-5 w-5" /> Lihat Website
                </a>
            </div>
        </aside>
        <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-border bg-surface/95 px-4 backdrop-blur sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button id="sidebar-toggle" class="btn-ghost btn-sm lg:hidden" aria-label="Buka menu"><x-icon name="menu" class="h-6 w-6" /></button>
                    <div class="min-w-0">
                        <h1 class="truncate text-lg sm:text-xl">{{ $title }}</h1>
                        @if ($subtitle)
                            <p class="hidden truncate text-xs text-text-muted sm:block">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if ($pending)
                        <a href="{{ route('admin.orders.index', ['payment_status' => 'waiting_confirmation']) }}" class="relative rounded-lg p-2 text-text-muted hover:bg-slate-100" title="{{ $pending }} pembayaran perlu dikonfirmasi">
                            <x-icon name="inbox" />
                            <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-accent"></span>
                        </a>
                    @endif
                    <details class="relative" id="user-menu">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg p-1.5 hover:bg-slate-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-light text-xs font-bold text-primary">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                            <span class="hidden text-sm font-medium sm:block">{{ $user->name }}</span>
                            <x-icon name="chevron-down" class="hidden h-4 w-4 text-text-muted sm:block" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-56 rounded-xl border border-border bg-surface p-1.5 shadow-lg">
                            <p class="truncate px-3 py-2 text-xs text-text-muted">{{ $user->email }}</p>
                            <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100"><x-icon name="user" class="h-4 w-4" /> Profil Saya</a>
                            <a href="{{ route('admin.settings.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100"><x-icon name="cog" class="h-4 w-4" /> Pengaturan</a>
                            <form action="{{ route('admin.logout') }}" method="POST" class="border-t border-border pt-1.5 mt-1.5">
                                @csrf
                                <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-danger hover:bg-red-50"><x-icon name="logout" class="h-4 w-4" /> Keluar</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>
            <main class="p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @include('partials.toast')
    <script>
        (() => {
            // Menu samping untuk layar kecil
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const toggle = (open) => {
                sidebar.classList.toggle('-translate-x-full', !open);
                backdrop.classList.toggle('hidden', !open);
            };
            document.getElementById('sidebar-toggle').addEventListener('click', () => toggle(true));
            backdrop.addEventListener('click', () => toggle(false));

            // Tutup menu akun saat klik di luar
            const userMenu = document.getElementById('user-menu');
            document.addEventListener('click', (e) => { if (!userMenu.contains(e.target)) userMenu.removeAttribute('open'); });
        })();

        // Konfirmasi sebelum aksi penting
        document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (e) => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        }));
    </script>
    @stack('scripts')
</body>
</html>
