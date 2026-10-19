@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
    $pay = $order->payment_snapshot ?? [];
    $paidFailed = $order->status === OrderStatus::Failed && $order->payment_status === PaymentStatus::Paid;
@endphp
<x-layouts.admin :title="'Transaksi '.$order->invoice">
    <a href="{{ route('admin.orders.index') }}" class="mb-4 inline-block text-sm text-text-muted hover:text-primary">← Kembali ke daftar transaksi</a>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card p-5">
                <div class="flex flex-wrap items-center gap-2">
                    <x-status-badge :status="$order->payment_status" />
                    <x-status-badge :status="$order->status" />
                    @if ($order->isExpired())
                        <span class="badge bg-red-100 text-red-700">Lewat batas bayar</span>
                    @endif
                </div>
                <dl class="mt-4 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                    @foreach ([
                        'Produk' => "{$order->brand_name} — {$order->product_name}",
                        'SKU Digiflazz' => $order->sku,
                        'ID Tujuan' => $order->customer_display,
                        'customer_no terkirim' => $order->customer_no,
                        'WhatsApp' => $order->whatsapp,
                        'Metode Bayar' => ($pay['name'] ?? '-').($order->isAutomaticPayment() ? ' (Duitku '.$pay['gateway_code'].')' : ''),
                        'Referensi Duitku' => $order->gateway_reference ?? '-',
                        'Dibuat' => $order->created_at->format('d/m/Y H:i:s'),
                        'Batas Bayar' => $order->expires_at?->format('d/m/Y H:i'),
                        'Dibayar' => $order->paid_at?->format('d/m/Y H:i:s') ?? '-',
                        'Selesai' => $order->completed_at?->format('d/m/Y H:i:s') ?? '-',
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs text-text-muted">{{ $label }}</dt>
                            <dd class="font-medium break-all">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="card p-5">
                <h3 class="mb-3">Respons Digiflazz</h3>
                @if ($order->ref_id)
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div><dt class="text-xs text-text-muted">Ref ID</dt><dd class="font-medium">{{ $order->ref_id }} <span class="text-xs text-text-muted">(percobaan ke-{{ $order->attempts }})</span></dd></div>
                        <div><dt class="text-xs text-text-muted">RC</dt><dd class="font-medium">{{ $order->provider_rc ?? '-' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-text-muted">Pesan</dt><dd class="font-medium">{{ $order->provider_message ?? '-' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-text-muted">SN</dt><dd class="font-medium break-all">{{ $order->sn ?? '-' }}</dd></div>
                    </dl>
                @else
                    <p class="text-text-muted">Belum dikirim ke Digiflazz. Transaksi dikirim otomatis setelah pembayaran dikonfirmasi.</p>
                @endif
            </section>

            <section class="card p-5">
                <h3 class="mb-3">Bukti Pembayaran</h3>
                @if ($order->payment_proof)
                    <a href="{{ route('admin.orders.proof', $order) }}" target="_blank">
                        <img src="{{ route('admin.orders.proof', $order) }}" alt="Bukti pembayaran {{ $order->invoice }}" class="max-h-[480px] rounded-lg border border-border">
                    </a>
                    <p class="hint">Klik gambar untuk memperbesar.</p>
                @elseif ($order->isAutomaticPayment())
                    <p class="text-text-muted">Pembayaran otomatis via Duitku — tidak memerlukan bukti transfer. Status lunas dari callback Duitku.</p>
                @else
                    <p class="text-text-muted">Pembeli belum mengunggah bukti pembayaran.</p>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-5">
                <h3 class="mb-3">Rincian Biaya</h3>
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-text-muted">Harga Jual</dt><dd>{{ rupiah($order->selling_price) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-text-muted">Biaya Metode</dt><dd>{{ rupiah($order->fee) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-text-muted">Kode Unik</dt><dd>{{ rupiah($order->unique_code) }}</dd></div>
                    <div class="flex justify-between border-t border-border pt-2 font-bold"><dt>Harus Diterima</dt><dd class="text-accent-dark">{{ rupiah($order->total) }}</dd></div>
                    <div class="flex justify-between pt-2"><dt class="text-text-muted">Modal</dt><dd>{{ rupiah($order->provider_price ?? $order->base_price) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-text-muted">Laba Produk</dt><dd class="font-semibold text-success">{{ rupiah($order->profit()) }}</dd></div>
                </dl>
            </section>

            <section class="card space-y-3 p-5">
                <h3>Aksi</h3>

                @if ($order->canBeConfirmed())
                    @if ($order->isAutomaticPayment())
                        <p class="text-xs text-text-muted">Pembayaran Duitku dikonfirmasi otomatis. Konfirmasi manual hanya bila sudah dicek lunas di dashboard Duitku.</p>
                    @else
                        <p class="text-xs text-text-muted">Cocokkan mutasi rekening sebesar <b>{{ rupiah($order->total) }}</b> sebelum konfirmasi.</p>
                    @endif
                    <form action="{{ route('admin.orders.confirm', $order) }}" method="POST" data-confirm="Konfirmasi pembayaran {{ rupiah($order->total) }} dan kirim ke Digiflazz?">
                        @csrf
                        <button class="btn-primary w-full">✓ Konfirmasi Lunas & Proses</button>
                    </form>
                    <form action="{{ route('admin.orders.reject', $order) }}" method="POST" class="space-y-2 border-t border-border pt-3" data-confirm="Tolak pembayaran dan batalkan pesanan ini?">
                        @csrf
                        <label for="reject_note" class="label">Alasan penolakan (tampil ke pembeli)</label>
                        <input id="reject_note" name="admin_note" class="input" placeholder="Contoh: Dana belum masuk">
                        <button class="btn-danger w-full">Tolak Pembayaran</button>
                    </form>
                @endif

                @if ($order->status === OrderStatus::Processing || $paidFailed)
                    <form action="{{ route('admin.orders.check', $order) }}" method="POST">
                        @csrf
                        <button class="btn-secondary w-full">Cek Status ke Digiflazz</button>
                    </form>
                @endif

                @if ($paidFailed)
                    <form action="{{ route('admin.orders.retry', $order) }}" method="POST" data-confirm="Kirim ulang transaksi ke Digiflazz dengan ref_id baru?">
                        @csrf
                        <button class="btn-primary w-full">Ulangi Transaksi</button>
                    </form>
                    <form action="{{ route('admin.orders.refund', $order) }}" method="POST" class="space-y-2 border-t border-border pt-3" data-confirm="Tandai pesanan ini sudah di-refund ke pembeli?">
                        @csrf
                        <label for="refund_note" class="label">Catatan refund (tampil ke pembeli)</label>
                        <input id="refund_note" name="admin_note" class="input" placeholder="Contoh: Refund ke BCA 1234 a.n. Budi">
                        <button class="btn-danger w-full">Tandai Sudah Refund</button>
                    </form>
                @endif

                @if (! $order->canBeConfirmed() && $order->status !== OrderStatus::Processing && ! $paidFailed)
                    <p class="text-text-muted">Tidak ada aksi untuk pesanan ini.</p>
                @endif

                <div class="flex flex-col gap-2 border-t border-border pt-3">
                    <a href="https://wa.me/{{ $order->whatsapp }}?text={{ urlencode("Halo, terkait pesanan {$order->invoice}: ".route('orders.show', $order)) }}" target="_blank" rel="noopener" class="btn-ghost btn-sm">Chat Pembeli via WhatsApp</a>
                    <a href="{{ route('orders.show', $order) }}" target="_blank" class="btn-ghost btn-sm">Lihat Halaman Invoice ↗</a>
                </div>
            </section>

            @if ($order->admin_note)
                <section class="card p-5">
                    <h3 class="mb-2">Catatan Admin</h3>
                    <p class="text-text-muted">{{ $order->admin_note }}</p>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.admin>
