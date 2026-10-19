<x-layouts.admin title="Dashboard">
    {{-- Peringatan setup --}}
    <div class="mb-6 space-y-3">
        @if ($balanceError)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="font-semibold">Koneksi Digiflazz bermasalah</p>
                <p class="mt-1">{{ $balanceError }}</p>
                <p class="mt-1 text-xs">Periksa DIGIFLAZZ_USERNAME, DIGIFLAZZ_KEY di file .env, dan pastikan IP server sudah di-whitelist di dashboard Digiflazz.</p>
            </div>
        @endif
        @if ($isTesting)
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <b>Mode testing aktif.</b> Transaksi tidak memotong saldo dan tidak benar-benar dikirim. Ubah <code>DIGIFLAZZ_TESTING=false</code> dan pakai production key saat siap jualan.
            </div>
        @endif
        @if (! $hasPaymentMethod)
            <div class="flex flex-col gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 sm:flex-row sm:items-center sm:justify-between">
                <p>Belum ada metode pembayaran aktif — pembeli belum bisa memesan.</p>
                <a href="{{ route('admin.payment-methods.create') }}" class="btn-primary btn-sm">Tambah Metode</a>
            </div>
        @endif
        @if ($stats['products'] === 0)
            <div class="flex flex-col gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 sm:flex-row sm:items-center sm:justify-between">
                <p>Produk masih kosong. Tarik daftar produk & harga dari Digiflazz.</p>
                <form action="{{ route('admin.products.sync') }}" method="POST">@csrf<button class="btn-primary btn-sm">Sinkronkan Sekarang</button></form>
            </div>
        @endif
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card col-span-2 bg-primary p-5 text-white lg:col-span-1">
            <p class="text-xs text-primary-light">Saldo Digiflazz</p>
            <p class="mt-1 text-2xl font-bold">{{ $balance !== null ? rupiah($balance) : '—' }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-text-muted">Pesanan Hari Ini</p>
            <p class="mt-1 text-2xl font-bold">{{ number_format($stats['orders_today']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-text-muted">Omzet Hari Ini</p>
            <p class="mt-1 text-2xl font-bold">{{ rupiah($stats['revenue_today']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-text-muted">Laba Hari Ini</p>
            <p class="mt-1 text-2xl font-bold text-success">{{ rupiah($stats['profit_today']) }}</p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4">
        <a href="{{ route('admin.orders.index', ['payment_status' => 'waiting_confirmation']) }}" class="card p-5 transition hover:border-primary">
            <p class="text-xs text-text-muted">Perlu Konfirmasi Pembayaran</p>
            <p class="mt-1 text-2xl font-bold text-accent-dark">{{ $stats['need_confirmation'] }}</p>
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'processing']) }}" class="card p-5 transition hover:border-primary">
            <p class="text-xs text-text-muted">Sedang Diproses Digiflazz</p>
            <p class="mt-1 text-2xl font-bold">{{ $stats['processing'] }}</p>
        </a>
    </div>

    {{-- Transaksi terbaru --}}
    <section class="card mt-6">
        <div class="flex items-center justify-between border-b border-border px-5 py-4">
            <h3>Transaksi Terbaru</h3>
            <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-primary hover:underline">Lihat semua</a>
        </div>
        @include('admin.orders.table', ['orders' => $latestOrders])
    </section>
</x-layouts.admin>
