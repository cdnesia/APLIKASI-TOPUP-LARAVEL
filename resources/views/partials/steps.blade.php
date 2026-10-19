@php
    $steps = [
        ['cube', 'Pilih produk', 'Pilih game atau produk digital yang ingin diisi.'],
        ['user', 'Masukkan ID', 'Isi ID akun atau nomor tujuan, lalu pilih nominal.'],
        ['wallet', 'Bayar', 'Transfer sesuai total, lalu unggah bukti pembayaran.'],
        ['check-circle', 'Selesai', 'Pesanan diproses otomatis dan masuk ke akunmu.'],
    ];
@endphp
<ol class="relative grid gap-6 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4">
    <span class="pointer-events-none absolute top-5 right-[12%] left-[12%] hidden h-px bg-border-dark lg:block" aria-hidden="true"></span>
    @foreach ($steps as $i => [$icon, $title, $text])
        <li class="relative flex gap-4 lg:flex-col lg:items-center lg:text-center">
            <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-border-dark bg-surface text-sm font-semibold text-primary shadow-soft">{{ $i + 1 }}</span>
            <div>
                <h3 class="text-base">{{ $title }}</h3>
                <p class="mt-1 max-w-[220px] leading-relaxed text-text-muted">{{ $text }}</p>
            </div>
        </li>
    @endforeach
</ol>
