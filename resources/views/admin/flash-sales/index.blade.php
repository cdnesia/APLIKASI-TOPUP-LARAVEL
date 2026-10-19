<x-layouts.admin title="Flash Sale" subtitle="Harga khusus dengan kuota dan batas waktu">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-text-muted">Kuota terhitung saat order dibuat dan dikembalikan bila order kedaluwarsa, ditolak, atau di-refund.</p>
        <a href="{{ route('admin.flash-sales.create') }}" class="btn-primary shrink-0">+ Buat Flash Sale</a>
    </div>

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Produk</th><th>Harga Flash</th><th>Laba / item</th><th>Terjual</th><th>Periode</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($flashSales as $sale)
                        @php
                            $product = $sale->product;
                            $profit = $sale->price - $product->base_price;
                            $statusClass = match ($sale->status()) {
                                'Berlangsung' => 'bg-green-100 text-green-800',
                                'Terjadwal' => 'bg-blue-100 text-blue-800',
                                'Habis' => 'bg-amber-100 text-amber-800',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-product-thumb :product="$product" size="h-10 w-10" />
                                    <div>
                                        <p class="font-medium">{{ $product->name }}</p>
                                        <p class="text-xs text-text-muted">{{ $product->brand?->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">
                                <p class="font-semibold text-accent-dark">{{ rupiah($sale->price) }}</p>
                                <p class="text-xs text-text-muted line-through">{{ rupiah($product->selling_price) }}</p>
                            </td>
                            <td @class(['whitespace-nowrap font-medium', 'text-success' => $profit >= 0, 'text-danger' => $profit < 0])>
                                {{ $profit < 0 ? '−'.rupiah(abs($profit)) : rupiah($profit) }}
                                @if ($profit < 0) <p class="text-xs font-normal">Rugi per item</p> @endif
                            </td>
                            <td class="min-w-[120px]">
                                <p class="text-xs">{{ $sale->sold }} / {{ $sale->quota }}</p>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-accent" style="width: {{ $sale->soldPercent() }}%"></div></div>
                            </td>
                            <td class="text-xs whitespace-nowrap text-text-muted">{{ $sale->starts_at->format('d/m H:i') }} – {{ $sale->ends_at->format('d/m H:i') }}</td>
                            <td><span class="badge {{ $statusClass }}">{{ $sale->status() }}</span></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.flash-sales.edit', $sale) }}" class="btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('admin.flash-sales.destroy', $sale) }}" method="POST" class="inline" data-confirm="Hapus flash sale ini?">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost btn-sm text-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center">
                                <p class="font-medium">Belum ada flash sale</p>
                                <p class="mt-1 text-xs text-text-muted">Flash sale tampil di beranda dengan hitung mundur dan sisa kuota.</p>
                                <a href="{{ route('admin.flash-sales.create') }}" class="btn-primary btn-sm mt-3">Buat Flash Sale Pertama</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($flashSales->hasPages())
            <div class="px-4 py-3">{{ $flashSales->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
