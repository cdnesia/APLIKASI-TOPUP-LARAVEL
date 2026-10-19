<x-layouts.app title="Cara Order & FAQ">
    <x-page-header eyebrow="Bantuan" title="Cara Order" subtitle="Panduan lengkap melakukan top up dan jawaban atas pertanyaan yang sering diajukan." />

    <div class="mx-auto max-w-6xl space-y-14 px-4 pt-10">
        <section>
            <h2 class="mb-6">4 Langkah Top Up</h2>
            @include('partials.steps')
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="card p-6">
                <h3>Detail Langkah Pembayaran</h3>
                <ol class="mt-4 space-y-3 text-text-muted">
                    @foreach ([
                        'Setelah klik "Beli Sekarang", Anda akan diarahkan ke halaman invoice. Simpan nomor invoice.',
                        'Transfer sesuai total yang tertera, termasuk 3 digit kode unik.',
                        'Unggah foto atau screenshot bukti transfer di halaman invoice.',
                        'Admin memverifikasi pembayaran, lalu pesanan diproses otomatis.',
                        'Status berubah menjadi "Sukses" dan item masuk ke akun Anda.',
                    ] as $i => $text)
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-white">{{ $i + 1 }}</span>
                            <span>{{ $text }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
            <div class="card p-6">
                <h3>Metode Pembayaran</h3>
                @if ($paymentMethods->isEmpty())
                    <p class="mt-4 text-text-muted">Metode pembayaran sedang disiapkan.</p>
                @else
                    <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($paymentMethods as $method)
                            <li class="flex items-center gap-3 rounded-lg border border-border p-3">
                                @if ($method->logoUrl())
                                    <img src="{{ $method->logoUrl() }}" alt="" class="h-9 w-14 shrink-0 rounded-lg border border-border bg-white object-contain p-1">
                                @else
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-text-muted"><x-icon :name="$method->type === 'qris' ? 'squares' : ($method->type === 'ewallet' ? 'wallet' : 'card')" /></span>
                                @endif
                                <div>
                                    <p class="font-medium">{{ $method->name }}</p>
                                    <p class="text-xs text-text-muted">{{ $method->typeLabel() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <p class="mt-4 flex items-start gap-2 rounded-lg bg-accent-light p-3 text-xs text-accent-dark">
                    <x-icon name="clock" class="h-4 w-4 shrink-0" /> Konfirmasi pembayaran: {{ \App\Models\Setting::get('operational_hours') }}
                </p>
            </div>
        </section>

        <section id="faq" class="scroll-mt-20">
            <h2 class="mb-6">Pertanyaan Umum (FAQ)</h2>
            @include('partials.faq')
        </section>

        <section class="relative flex flex-col items-start justify-between gap-4 overflow-hidden rounded-[28px] bg-ink p-8 text-white sm:flex-row sm:items-center sm:p-10">
            <div class="pointer-events-none absolute -top-24 -right-10 h-64 w-64 rounded-full bg-primary/40 blur-[100px]"></div>
            <div>
                <h2 class="relative text-xl">Siap top up sekarang?</h2>
                <p class="relative mt-1 text-slate-400">Pilih produk favoritmu dan selesaikan dalam hitungan menit.</p>
            </div>
            <a href="{{ route('home') }}#produk" class="btn relative shrink-0 bg-white text-ink hover:bg-slate-100">Mulai Top Up</a>
        </section>
    </div>
</x-layouts.app>
