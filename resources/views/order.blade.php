@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
    $pay = $order->payment_snapshot ?? [];
    $wa = \App\Models\Setting::get('whatsapp_admin');
    $automatic = $order->isAutomaticPayment();
    $autoRefresh = $order->status === OrderStatus::Processing
        || $order->payment_status === PaymentStatus::WaitingConfirmation
        || ($automatic && $order->payment_status === PaymentStatus::Unpaid);
@endphp
{{-- Invoice berisi data pembeli: jangan diindeks mesin pencari --}}
<x-layouts.app :title="'Invoice '.$order->invoice" noindex>
    {{-- Desktop: dua kolom (status & pembayaran | rincian). HP: bertumpuk --}}
    <div class="mx-auto grid max-w-6xl items-start gap-5 px-4 py-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-6">
        <div class="space-y-5">
        {{-- Status utama --}}
        <section class="card p-5 text-center sm:p-6">
            <p class="text-xs text-text-muted">Nomor Invoice</p>
            <div class="mt-1 flex items-center justify-center gap-2">
                <h1 class="text-xl tracking-wide sm:text-2xl">{{ $order->invoice }}</h1>
                <button type="button" class="btn-ghost btn-sm" data-copy="{{ $order->invoice }}" aria-label="Salin nomor invoice">Salin</button>
            </div>
            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <x-status-badge :status="$order->payment_status" />
                <x-status-badge :status="$order->status" />
            </div>

            @switch(true)
                @case($order->status === OrderStatus::Success)
                    <p class="mt-4 font-semibold text-success">✓ Top up berhasil! Terima kasih telah berbelanja.</p>
                    @if ($order->sn)
                        <div class="mt-3 rounded-lg bg-green-50 p-3 text-left text-xs">
                            <p class="font-semibold text-green-800">Serial Number / Keterangan</p>
                            <p class="mt-1 break-all text-green-900">{{ $order->sn }}</p>
                        </div>
                    @endif
                    @break
                @case($order->status === OrderStatus::Processing)
                    <p class="mt-4 text-text-muted">Pembayaran diterima. Pesanan sedang diproses, biasanya kurang dari 5 menit.</p>
                    @break
                @case($order->status === OrderStatus::Failed)
                    <p class="mt-4 text-danger">Transaksi gagal diproses. Admin akan memproses ulang atau mengembalikan dana Anda.</p>
                    @break
                @case($order->payment_status === PaymentStatus::WaitingConfirmation)
                    <p class="mt-4 text-text-muted">Bukti pembayaran sudah diterima dan sedang dicek admin.</p>
                    @break
                @case($order->payment_status === PaymentStatus::Expired)
                    <p class="mt-4 text-danger">Batas waktu pembayaran habis. Silakan buat pesanan baru.</p>
                    @break
                @case($order->payment_status === PaymentStatus::Rejected)
                    <p class="mt-4 text-danger">Pembayaran ditolak{{ $order->admin_note ? ': '.$order->admin_note : '.' }}</p>
                    @break
                @case($order->payment_status === PaymentStatus::Refunded)
                    <p class="mt-4 text-info">Dana telah dikembalikan{{ $order->admin_note ? ': '.$order->admin_note : '.' }}</p>
                    @break
            @endswitch
        </section>

        {{-- Instruksi pembayaran --}}
        @if ($order->payment_status === PaymentStatus::Unpaid)
            <section class="card p-5 sm:p-6">
                <h3>Instruksi Pembayaran</h3>
                <p class="mt-1 text-xs text-text-muted">
                    Bayar sebelum <span class="font-semibold text-text">{{ $order->expires_at->translatedFormat('d M Y, H:i') }} WIB</span>
                    (<span id="countdown" data-deadline="{{ $order->expires_at->toIso8601String() }}">—</span>)
                </p>

                <div class="mt-4 rounded-xl bg-accent-light p-4 text-center">
                    <p class="text-xs font-medium text-accent-dark">Total yang harus dibayar</p>
                    <div class="mt-1 flex items-center justify-center gap-2">
                        <p class="text-2xl font-bold sm:text-3xl">{{ rupiah($order->total) }}</p>
                        <button type="button" class="btn-ghost btn-sm" data-copy="{{ $order->total }}" aria-label="Salin nominal">Salin</button>
                    </div>
                    @unless ($automatic)
                        <p class="mt-1 text-xs text-accent-dark">⚠ Transfer <b>tepat</b> sampai 3 digit terakhir agar cepat diverifikasi</p>
                    @endunless
                </div>

                @if ($automatic)
                    <div class="mt-4 rounded-lg border border-border p-4">
                        <p class="text-xs text-text-muted">Pembayaran otomatis</p>
                        <p class="font-semibold">{{ $pay['name'] ?? '-' }}</p>

                        @if ($order->qr_string)
                            <div id="qris" data-qr="{{ $order->qr_string }}" class="mx-auto mt-3 flex w-full max-w-[260px] justify-center rounded-lg border border-border bg-white p-3" role="img" aria-label="Kode QRIS untuk membayar {{ rupiah($order->total) }}"></div>
                            <p class="mt-2 text-center text-xs text-text-muted">Pindai dengan aplikasi bank atau e-wallet apa pun. Nominal sudah terisi otomatis.</p>
                        @elseif ($order->pay_code)
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs text-text-muted">Nomor Virtual Account / Kode Bayar</p>
                                    <p class="text-lg font-bold tracking-wider break-all">{{ $order->pay_code }}</p>
                                </div>
                                <button type="button" class="btn-secondary btn-sm" data-copy="{{ $order->pay_code }}">Salin</button>
                            </div>
                        @endif

                        @if ($order->checkout_url)
                            <a href="{{ $order->checkout_url }}" target="{{ $order->qr_string || $order->pay_code ? '_blank' : '_self' }}" rel="noopener"
                                class="{{ $order->qr_string || $order->pay_code ? 'btn-ghost' : 'btn-primary' }} mt-3 w-full">
                                {{ $order->qr_string || $order->pay_code ? 'Lihat cara bayar lengkap ↗' : 'Bayar Sekarang' }}
                            </a>
                        @endif

                        <p class="mt-3 border-t border-border pt-3 text-xs leading-relaxed text-text-muted">
                            Pesanan diproses otomatis begitu pembayaran diterima. Halaman ini memperbarui diri sendiri; tidak perlu unggah bukti.
                        </p>
                        <form action="{{ route('orders.check-payment', $order) }}" method="POST" class="mt-3">
                            @csrf
                            <button class="btn-secondary w-full">Saya Sudah Bayar — Cek Status</button>
                        </form>
                    </div>
                @else
                <div class="mt-4 rounded-lg border border-border p-4">
                    <p class="text-xs text-text-muted">{{ \App\Models\PaymentMethod::TYPES[$pay['type'] ?? ''] ?? 'Pembayaran' }}</p>
                    <p class="font-semibold">{{ $pay['name'] ?? '-' }}</p>
                    @if (($pay['type'] ?? null) === 'qris' && $order->paymentImageUrl())
                        <img src="{{ $order->paymentImageUrl() }}" alt="Kode QRIS {{ $pay['name'] }}" class="mx-auto mt-3 w-full max-w-xs rounded-lg border border-border">
                        <p class="mt-2 text-center text-xs text-text-muted">Pindai dengan aplikasi bank atau e-wallet apa pun, lalu masukkan nominal di atas.</p>
                    @endif
                    @if (! empty($pay['account_number']))
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-lg font-bold tracking-wider">{{ $pay['account_number'] }}</p>
                                <p class="text-xs text-text-muted">a.n. {{ $pay['account_name'] }}</p>
                            </div>
                            <button type="button" class="btn-secondary btn-sm" data-copy="{{ $pay['account_number'] }}">Salin</button>
                        </div>
                    @endif
                    @if (! empty($pay['instructions']))
                        <p class="mt-3 border-t border-border pt-3 text-xs leading-relaxed whitespace-pre-line text-text-muted">{{ $pay['instructions'] }}</p>
                    @endif
                </div>
                @endif
            </section>
        @endif

        {{-- Upload bukti --}}
        @if ($order->canUploadProof())
            <section class="card p-5 sm:p-6">
                <h3>{{ $order->payment_proof ? 'Ganti Bukti Pembayaran' : 'Sudah Bayar? Unggah Bukti' }}</h3>
                <form action="{{ route('orders.proof', $order) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="proof" class="label">Foto / screenshot bukti transfer</label>
                        <input id="proof" name="proof" type="file" accept="image/*" required
                            class="input file:mr-3 file:rounded-md file:border-0 file:bg-primary-light file:px-3 file:py-1 file:text-xs file:font-semibold file:text-primary @error('proof') input-error @enderror">
                        @error('proof') <p class="field-error">{{ $message }}</p> @else <p class="hint">Format JPG/PNG, maksimal 4 MB.</p> @enderror
                    </div>
                    <button class="btn-primary w-full sm:w-auto">Kirim Bukti Pembayaran</button>
                </form>
            </section>
        @endif

        </div>

        <aside class="space-y-4 lg:sticky lg:top-36">
        {{-- Rincian pesanan --}}
        <section class="card p-5 sm:p-6">
            <h3 class="mb-3">Rincian Pesanan</h3>
            <dl class="divide-y divide-border text-sm">
                @foreach ([
                    'Produk' => $order->brand_name,
                    'Item' => $order->product_name,
                    'ID Tujuan' => $order->customer_display,
                    'Metode Bayar' => $pay['name'] ?? '-',
                    'Waktu Pesan' => $order->created_at->translatedFormat('d M Y, H:i').' WIB',
                ] as $label => $value)
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-text-muted">{{ $label }}</dt>
                        <dd class="text-right font-medium">{{ $value }}</dd>
                    </div>
                @endforeach
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-text-muted">Harga</dt><dd class="font-medium">{{ rupiah($order->selling_price) }}</dd>
                </div>
                @if ($order->fee)
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-text-muted">Biaya Admin</dt><dd class="font-medium">{{ rupiah($order->fee) }}</dd>
                    </div>
                @endif
                @if ($order->unique_code)
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-text-muted">Kode Unik</dt><dd class="font-medium">{{ rupiah($order->unique_code) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 pt-3">
                    <dt class="font-semibold">Total</dt><dd class="text-base font-bold">{{ rupiah($order->total) }}</dd>
                </div>
            </dl>
        </section>

        <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
            <a href="{{ route('home') }}" class="btn-secondary">Belanja Lagi</a>
            @if ($wa)
                <a href="https://wa.me/{{ $wa }}?text={{ urlencode("Halo admin, saya ingin menanyakan pesanan {$order->invoice}") }}" target="_blank" rel="noopener" class="btn-ghost">Hubungi CS</a>
            @endif
        </div>
        </aside>
    </div>

    @push('scripts')
        @if ($automatic && $order->qr_string && $order->payment_status === PaymentStatus::Unpaid)
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>
                // Render QRIS dinamis dari qrString Duitku
                (() => {
                    const el = document.getElementById('qris');
                    try {
                        new QRCode(el, { text: el.dataset.qr, width: 232, height: 232, correctLevel: QRCode.CorrectLevel.M });
                    } catch {
                        el.innerHTML = '<p class="text-xs text-text-muted text-center">QR gagal dimuat. Gunakan tombol "Lihat cara bayar lengkap".</p>';
                    }
                })();
            </script>
        @endif
        <script>
            // Tombol salin
            document.querySelectorAll('[data-copy]').forEach((btn) => btn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(btn.dataset.copy);
                    showToast('✓ Berhasil disalin', 'success');
                } catch {
                    showToast('✕ Gagal menyalin. Salin secara manual.', 'error');
                }
            }));

            // Hitung mundur batas pembayaran
            const countdown = document.getElementById('countdown');
            if (countdown) {
                const deadline = new Date(countdown.dataset.deadline).getTime();
                const tick = () => {
                    const left = Math.max(0, deadline - Date.now());
                    if (left === 0) return location.reload();
                    const h = Math.floor(left / 36e5), m = Math.floor(left % 36e5 / 6e4), s = Math.floor(left % 6e4 / 1e3);
                    countdown.textContent = `sisa ${h}j ${m}m ${s}d`;
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
