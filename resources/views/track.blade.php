<x-layouts.app title="Cek Pesanan">
    <div class="mx-auto max-w-md px-4 py-12">
        <section class="card p-6 sm:p-8">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-light text-primary"><x-icon name="receipt" class="h-6 w-6" /></span>
            <h1 class="mt-5 text-2xl">Cek Pesanan</h1>
            <p class="mt-1 text-text-muted">Masukkan nomor invoice untuk melihat status pesanan Anda.</p>
            <form action="{{ route('track') }}" method="GET" class="mt-5 space-y-4">
                <div>
                    <label for="invoice" class="label">Nomor Invoice</label>
                    <input id="invoice" name="invoice" value="{{ old('invoice') }}" required class="input uppercase" placeholder="Contoh: ELH261018ABC123">
                    <p class="hint">Nomor invoice tertera di halaman setelah Anda memesan.</p>
                </div>
                <button class="btn-primary w-full">Cek Status</button>
            </form>
        </section>
        <p class="mt-4 text-center text-xs text-text-muted">
            Belum punya pesanan? <a href="{{ route('home') }}" class="font-medium text-primary hover:underline">Mulai top up</a>
        </p>
    </div>
</x-layouts.app>
