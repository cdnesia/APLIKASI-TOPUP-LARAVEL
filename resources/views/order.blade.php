@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
    $pay = $order->payment_snapshot ?? [];
    $wa = \App\Models\Setting::get('whatsapp_admin');
    $automatic = $order->isAutomaticPayment();
    $unpaid = $order->payment_status === PaymentStatus::Unpaid;
    $autoRefresh = $order->status === OrderStatus::Processing
        || $order->payment_status === PaymentStatus::WaitingConfirmation
        || ($automatic && $unpaid);

    // Satu judul status yang mudah dipahami pembeli: [warna, ikon, judul, keterangan]
    [$tone, $icon, $title, $desc] = match (true) {
        $order->status === OrderStatus::Success => ['success', 'check-circle', 'Top up berhasil', 'Terima kasih telah berbelanja.'],
        $order->status === OrderStatus::Processing => ['warning', 'refresh', 'Pesanan sedang diproses', 'Pembayaran diterima. Biasanya selesai kurang dari 5 menit.'],
        $order->status === OrderStatus::Failed => ['danger', 'x', 'Transaksi gagal', 'Admin akan memproses ulang atau mengembalikan dana Anda.'],
        $order->status === OrderStatus::Cancelled => ['danger', 'x', 'Pesanan dibatalkan', $order->admin_note ?: 'Silakan buat pesanan baru.'],
        $order->payment_status === PaymentStatus::WaitingConfirmation => ['warning', 'clock', 'Menunggu konfirmasi admin', 'Bukti pembayaran sudah diterima dan sedang dicek.'],
        $order->payment_status === PaymentStatus::Expired => ['danger', 'clock', 'Waktu pembayaran habis', 'Silakan buat pesanan baru.'],
        $order->payment_status === PaymentStatus::Rejected => ['danger', 'x', 'Pembayaran ditolak', $order->admin_note ?: 'Hubungi CS bila ada pertanyaan.'],
        $order->payment_status === PaymentStatus::Refunded => ['info', 'wallet', 'Dana telah dikembalikan', $order->admin_note ?: 'Dana sudah dikirim kembali ke Anda.'],
        $order->payment_status === PaymentStatus::Paid => ['warning', 'refresh', 'Pembayaran diterima', 'Pesanan segera diproses.'],
        default => ['primary', 'receipt', 'Selesaikan pembayaran', 'Bayar sebelum '.$order->expires_at->translatedFormat('d M Y, H:i').' WIB'],
    };
    $toneClass = [
        'success' => 'bg-green-50 text-success',
        'warning' => 'bg-amber-50 text-warning',
        'danger' => 'bg-red-50 text-danger',
        'info' => 'bg-blue-50 text-info',
        'primary' => 'bg-primary-light text-primary',
    ][$tone];
@endphp
{{-- Invoice berisi data pembeli: jangan diindeks mesin pencari --}}
<x-layouts.app :title="'Invoice '.$order->invoice" noindex>
    {{-- Desktop: dua kolom (status & pembayaran | rincian). HP: bertumpuk --}}
    <div class="mx-auto grid max-w-6xl items-start gap-5 px-4 py-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-6">
        <div class="space-y-4">

        {{-- Satu kartu ringkas: status di atas, QR di kiri & info bayar di kanan (desktop) --}}
        @php
            $qrManual = ! $automatic && ($pay['type'] ?? null) === 'qris' && $order->paymentImageUrl();
            $hasQr = $unpaid && (($automatic && $order->qr_string) || $qrManual);
        @endphp
        <section class="card">
            <div class="flex flex-wrap items-center gap-3 border-b border-border px-5 py-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $toneClass }}">
                    <x-icon :name="$icon" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h1 class="text-lg leading-tight">{{ $title }}</h1>
                    <p class="text-xs text-text-muted">{{ $desc }}</p>
                </div>
                @if ($unpaid)
                    <p class="inline-flex w-full shrink-0 items-center justify-center gap-1.5 rounded-full bg-accent-light px-3 py-1 text-xs font-semibold text-accent-dark sm:w-auto">
                        <x-icon name="clock" class="h-4 w-4" /> Sisa waktu <span id="countdown" data-deadline="{{ $order->expires_at->toIso8601String() }}">—</span>
                    </p>
                @endif
            </div>

            <div class="grid gap-4 p-5 {{ $hasQr ? 'sm:grid-cols-[212px_minmax(0,1fr)] sm:items-start sm:gap-5' : '' }}">
                @if ($hasQr)
                    <div class="text-center">
                        @if ($automatic)
                            <div id="qris" data-qr="{{ $order->qr_string }}" class="mx-auto flex w-[212px] justify-center rounded-lg border border-border bg-white p-2" role="img" aria-label="Kode QRIS untuk membayar {{ rupiah($order->total) }}"></div>
                        @else
                            <img src="{{ $order->paymentImageUrl() }}" alt="Kode QRIS {{ $pay['name'] }}" class="mx-auto w-[212px] rounded-lg border border-border">
                        @endif
                        <p class="mt-1.5 text-[11px] leading-snug text-text-muted">{{ $automatic ? 'Pindai dengan aplikasi bank / e-wallet apa pun. Nominal terisi otomatis.' : 'Pindai, lalu masukkan nominal sesuai total.' }}</p>
                    </div>
                @endif

                <div class="min-w-0 space-y-3">
                    @if ($order->status === OrderStatus::Success && $order->sn)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-green-50 p-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-green-800">Serial Number / Keterangan</p>
                                <p class="mt-0.5 text-sm break-all text-green-900">{{ $order->sn }}</p>
                            </div>
                            <x-copy-button :value="$order->sn" name="serial number" />
                        </div>
                    @endif

                    @if ($unpaid)
                        <div class="flex items-center gap-3">
                            @if ($logo = $order->paymentLogoUrl())
                                <img src="{{ $logo }}" alt="" class="h-9 w-14 shrink-0 rounded-lg border border-border bg-white object-contain p-1">
                            @endif
                            <div class="min-w-0">
                                <p class="font-semibold leading-tight">{{ $pay['name'] ?? '-' }}</p>
                                <p class="text-xs text-text-muted">{{ $automatic ? 'Dicek otomatis, tanpa unggah bukti' : (\App\Models\PaymentMethod::TYPES[$pay['type'] ?? ''] ?? 'Pembayaran') }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Data yang bisa disalin --}}
                    <dl class="divide-y divide-border rounded-lg border border-border">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <div class="min-w-0">
                                <dt class="text-xs text-text-muted">No. Invoice</dt>
                                <dd class="font-semibold tracking-wide break-all">{{ $order->invoice }}</dd>
                            </div>
                            <x-copy-button :value="$order->invoice" name="nomor invoice" />
                        </div>
                        @if ($unpaid)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <div>
                                    <dt class="text-xs text-text-muted">Total Bayar</dt>
                                    <dd class="text-xl font-bold">{{ rupiah($order->total) }}</dd>
                                </div>
                                <x-copy-button :value="$order->total" name="nominal" />
                            </div>
                            @if ($automatic && ! $order->qr_string && $order->pay_code)
                                <div class="flex items-center justify-between gap-3 px-3 py-2">
                                    <div class="min-w-0">
                                        <dt class="text-xs text-text-muted">Nomor Virtual Account / Kode Bayar</dt>
                                        <dd class="text-lg font-bold tracking-wider break-all">{{ $order->pay_code }}</dd>
                                    </div>
                                    <x-copy-button :value="$order->pay_code" name="nomor virtual account" />
                                </div>
                            @elseif (! $automatic && ! empty($pay['account_number']))
                                <div class="flex items-center justify-between gap-3 px-3 py-2">
                                    <div class="min-w-0">
                                        <dt class="text-xs text-text-muted">Nomor Rekening / Akun · a.n. {{ $pay['account_name'] }}</dt>
                                        <dd class="text-lg font-bold tracking-wider break-all">{{ $pay['account_number'] }}</dd>
                                    </div>
                                    <x-copy-button :value="$pay['account_number']" name="nomor rekening" />
                                </div>
                            @endif
                        @endif
                    </dl>

                    @if ($unpaid && ! $automatic)
                        <p class="rounded-lg bg-accent-light px-3 py-2 text-xs text-accent-dark">⚠ Transfer <b>tepat</b> sampai 3 digit terakhir agar cepat diverifikasi.</p>
                        @if (! empty($pay['instructions']))
                            <p class="text-xs leading-relaxed whitespace-pre-line text-text-muted">{{ $pay['instructions'] }}</p>
                        @endif
                    @endif

                    @if ($unpaid && $automatic)
                        <div class="space-y-1.5">
                            @if ($order->checkout_url && ! $order->qr_string && ! $order->pay_code)
                                <a href="{{ $order->checkout_url }}" rel="noopener" class="btn-primary w-full">Bayar Sekarang</a>
                            @endif
                            <form action="{{ route('orders.check-payment', $order) }}" method="POST">
                                @csrf
                                <button class="{{ $order->qr_string || $order->pay_code ? 'btn-primary' : 'btn-secondary' }} w-full">Saya Sudah Bayar — Cek Status</button>
                            </form>
                            @if ($order->checkout_url && ($order->qr_string || $order->pay_code))
                                <a href="{{ $order->checkout_url }}" target="_blank" rel="noopener" class="btn-ghost btn-sm w-full">Lihat cara bayar lengkap ↗</a>
                            @endif
                            <p class="text-center text-[11px] text-text-muted">Halaman ini memperbarui diri sendiri setelah pembayaran diterima.</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Upload bukti (metode manual) --}}
        @if ($order->canUploadProof())
            <section class="card p-5">
                <h3>{{ $order->payment_proof ? 'Ganti Bukti Pembayaran' : 'Sudah Bayar? Unggah Bukti' }}</h3>
                <form action="{{ route('orders.proof', $order) }}" method="POST" enctype="multipart/form-data" class="mt-3 space-y-3">
                    @csrf
                    <div>
                        <label for="proof" class="label">Foto / screenshot bukti transfer</label>
                        <input id="proof" name="proof" type="file" accept="image/*" required
                            class="input file:mr-3 file:rounded-md file:border-0 file:bg-primary-light file:px-3 file:py-1 file:text-xs file:font-semibold file:text-primary @error('proof') input-error @enderror">
                        @error('proof') <p class="field-error">{{ $message }}</p> @else <p class="hint">Format JPG/PNG, maksimal 4 MB.</p> @enderror
                    </div>
                    <button class="btn-primary w-full">Kirim Bukti Pembayaran</button>
                </form>
            </section>
        @endif

        </div>

        <aside class="space-y-4 lg:sticky lg:top-36">
        {{-- Rincian pesanan --}}
        <section class="card p-5">
            <h3 class="mb-2">Rincian Pesanan</h3>
            <dl class="divide-y divide-border text-sm">
                @foreach (array_filter([
                    'Produk' => $order->brand_name,
                    'Item' => $order->product_name,
                    'ID Tujuan' => $order->customer_display,
                    'Waktu Pesan' => $order->created_at->translatedFormat('d M Y, H:i').' WIB',
                    'Harga' => rupiah($order->selling_price),
                    'Biaya Admin' => $order->fee ? rupiah($order->fee) : null,
                    'Kode Unik' => $order->unique_code ? rupiah($order->unique_code) : null,
                ]) as $label => $value)
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-text-muted">{{ $label }}</dt>
                        <dd class="text-right font-medium">{{ $value }}</dd>
                    </div>
                @endforeach
                <div class="flex justify-between gap-4 pt-3">
                    <dt class="font-semibold">Total</dt>
                    <dd class="text-base font-bold">{{ rupiah($order->total) }}</dd>
                </div>
            </dl>
        </section>

        <div class="grid gap-2 {{ $wa ? 'grid-cols-2' : '' }}">
            <a href="{{ route('home') }}" class="btn-secondary">Belanja Lagi</a>
            @if ($wa)
                <a href="https://wa.me/{{ $wa }}?text={{ urlencode("Halo admin, saya ingin menanyakan pesanan {$order->invoice}") }}" target="_blank" rel="noopener" class="btn-ghost">Hubungi CS</a>
            @endif
        </div>
        </aside>
    </div>

    @push('scripts')
        @if ($automatic && $order->qr_string && $unpaid)
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>
                // Render QRIS dinamis dari qrString Duitku
                (() => {
                    const el = document.getElementById('qris');
                    try {
                        new QRCode(el, { text: el.dataset.qr, width: 196, height: 196, correctLevel: QRCode.CorrectLevel.M });
                    } catch {
                        el.innerHTML = '<p class="text-xs text-text-muted text-center">QR gagal dimuat. Gunakan tombol "Lihat cara bayar lengkap".</p>';
                    }
                })();
            </script>
        @endif
        <script>
            // Hitung mundur batas pembayaran, format jj:mm:dd
            const countdown = document.getElementById('countdown');
            if (countdown) {
                const deadline = new Date(countdown.dataset.deadline).getTime();
                const pad = (n) => String(n).padStart(2, '0');
                const tick = () => {
                    const left = Math.max(0, deadline - Date.now());
                    if (left === 0) return location.reload();
                    const h = Math.floor(left / 36e5), m = Math.floor(left % 36e5 / 6e4), s = Math.floor(left % 6e4 / 1e3);
                    countdown.textContent = (h ? `${h}:` : '') + `${pad(m)}:${pad(s)}`;
                };
                tick();
                setInterval(tick, 1000);
            }

            @if ($autoRefresh)
                // Muat ulang otomatis selama pesanan masih diproses
                setTimeout(() => location.reload(), 20000);
            @endif
        </script>
    @endpush
</x-layouts.app>
