<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Produk</th>
                <th>Tujuan</th>
                <th>Total</th>
                <th>Pembayaran</th>
                <th>Status</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="hover:bg-slate-50">
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-primary hover:underline">{{ $order->invoice }}</a></td>
                    <td>
                        <p class="font-medium">{{ $order->product_name }}</p>
                        <p class="text-xs text-text-muted">{{ $order->brand_name }}</p>
                    </td>
                    <td class="whitespace-nowrap">{{ $order->customer_display }}</td>
                    <td class="whitespace-nowrap font-medium">{{ rupiah($order->total) }}</td>
                    <td><x-status-badge :status="$order->payment_status" /></td>
                    <td><x-status-badge :status="$order->status" /></td>
                    <td class="text-xs whitespace-nowrap text-text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-12 text-center">
                        <p class="font-medium">Belum ada transaksi</p>
                        <p class="mt-1 text-xs text-text-muted">Transaksi dari pembeli akan muncul di sini.</p>
                        <a href="{{ route('home') }}" target="_blank" class="btn-secondary btn-sm mt-3">Buka website</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
