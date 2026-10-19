@php($maxProfit = max(1, $daily->max('profit')))
<x-layouts.admin title="Laporan Laba" subtitle="Hanya transaksi berstatus Sukses yang dihitung">
    {{-- Filter periode --}}
    <form method="GET" class="card mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <div>
            <label for="dari" class="label">Dari</label>
            <input id="dari" name="dari" type="date" value="{{ $from->toDateString() }}" class="input @error('dari') input-error @enderror">
        </div>
        <div>
            <label for="sampai" class="label">Sampai</label>
            <input id="sampai" name="sampai" type="date" value="{{ $to->toDateString() }}" class="input @error('sampai') input-error @enderror">
            @error('sampai') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <button class="btn-primary">Tampilkan</button>
        <div class="flex flex-wrap gap-1 sm:ml-auto">
            @foreach ([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $days => $label)
                <a href="{{ route('admin.reports', ['dari' => now()->subDays($days - 1)->toDateString(), 'sampai' => now()->toDateString()]) }}" class="btn-ghost btn-sm">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card p-5">
            <p class="text-xs text-text-muted">Laba Produk</p>
            <p class="mt-1 text-2xl font-bold text-success">{{ rupiah($summary['profit']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-text-muted">Omzet</p>
            <p class="mt-1 text-2xl font-bold">{{ rupiah($summary['revenue']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-text-muted">Transaksi Sukses</p>
            <p class="mt-1 text-2xl font-bold">{{ number_format($summary['count']) }}</p>
            <p class="mt-1 text-xs text-text-muted">{{ $summary['failed'] }} gagal pada periode ini</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-text-muted">Biaya Admin + Kode Unik</p>
            <p class="mt-1 text-2xl font-bold">{{ rupiah($summary['extra']) }}</p>
            <p class="mt-1 text-xs text-text-muted">Pendapatan tambahan di luar laba produk</p>
        </div>
    </div>

    {{-- Grafik laba harian (satu seri) --}}
    <section class="card mt-6 p-5">
        <div class="flex items-baseline justify-between gap-3">
            <h3 class="text-base">Laba Harian</h3>
            <p class="text-xs text-text-muted">{{ $from->translatedFormat('d M Y') }} – {{ $to->translatedFormat('d M Y') }}</p>
        </div>
        @if ($summary['count'] === 0)
            <div class="mt-4 rounded-lg border border-dashed border-border-dark px-4 py-12 text-center">
                <p class="font-medium">Belum ada transaksi sukses pada periode ini</p>
                <p class="mt-1 text-xs text-text-muted">Coba pilih rentang tanggal lain.</p>
                <a href="{{ route('admin.reports', ['dari' => now()->subDays(89)->toDateString()]) }}" class="btn-secondary btn-sm mt-3">Lihat 90 hari terakhir</a>
            </div>
        @else
            <div class="mt-4 flex gap-3">
                <div class="flex h-48 flex-col justify-between text-right text-[11px] text-text-muted">
                    <span>{{ rupiah($maxProfit) }}</span><span>{{ rupiah($maxProfit / 2) }}</span><span>Rp0</span>
                </div>
                <div class="relative min-w-0 flex-1">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-48 border-y border-border">
                        <div class="absolute inset-x-0 top-1/2 border-t border-dashed border-border"></div>
                    </div>
                    <div class="relative flex h-48 items-end gap-0.5" id="profit-chart">
                        @foreach ($daily as $row)
                            <div class="group flex h-full flex-1 cursor-default items-end" tabindex="0"
                                data-tip="{{ $row['date']->translatedFormat('D, d M Y') }}|{{ rupiah($row['profit']) }}|{{ $row['count'] }} transaksi · omzet {{ rupiah($row['revenue']) }}">
                                <div class="w-full rounded-t bg-primary transition group-hover:bg-primary-dark group-focus:bg-primary-dark"
                                    style="height: {{ $row['profit'] > 0 ? max(2, $row['profit'] / $maxProfit * 100) : 0 }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between text-[11px] text-text-muted">
                        <span>{{ $from->translatedFormat('d M') }}</span>
                        <span>{{ $to->translatedFormat('d M') }}</span>
                    </div>
                    <div id="chart-tip" class="pointer-events-none absolute z-10 hidden rounded-lg border border-border bg-surface px-3 py-2 text-xs shadow-lg"></div>
                </div>
            </div>
        @endif
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        {{-- Produk paling menguntungkan --}}
        <section class="card">
            <h3 class="border-b border-border px-5 py-4 text-base">Produk Paling Menguntungkan</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Produk</th><th class="text-right">Terjual</th><th class="text-right">Laba</th></tr></thead>
                    <tbody>
                        @forelse ($topProducts as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-right">{{ $row['count'] }}</td>
                                <td class="text-right font-semibold whitespace-nowrap text-success">{{ rupiah($row['profit']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 text-center text-text-muted">Belum ada data penjualan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Tabel harian (tampilan tabel untuk grafik) --}}
        <section class="card">
            <h3 class="border-b border-border px-5 py-4 text-base">Rincian per Hari</h3>
            <div class="table-wrap max-h-96 overflow-y-auto">
                <table class="table">
                    <thead class="sticky top-0"><tr><th>Tanggal</th><th class="text-right">Transaksi</th><th class="text-right">Omzet</th><th class="text-right">Laba</th></tr></thead>
                    <tbody>
                        @foreach ($daily->reverse() as $row)
                            <tr @class(['text-text-muted' => ! $row['count']])>
                                <td class="whitespace-nowrap">{{ $row['date']->translatedFormat('d M Y') }}</td>
                                <td class="text-right">{{ $row['count'] }}</td>
                                <td class="text-right whitespace-nowrap">{{ rupiah($row['revenue']) }}</td>
                                <td class="text-right font-semibold whitespace-nowrap">{{ rupiah($row['profit']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            // Tooltip grafik (hover & fokus keyboard)
            (() => {
                const chart = document.getElementById('profit-chart');
                const tip = document.getElementById('chart-tip');
                if (!chart) return;
                const show = (bar) => {
                    const [date, profit, meta] = bar.dataset.tip.split('|');
                    tip.innerHTML = '';
                    [[date, 'text-text-muted'], [profit, 'text-sm font-bold text-text'], [meta, 'text-text-muted']].forEach(([text, cls]) => {
                        const p = document.createElement('p'); p.className = cls; p.textContent = text; tip.appendChild(p);
                    });
                    tip.classList.remove('hidden');
                    const box = chart.getBoundingClientRect(), b = bar.getBoundingClientRect();
                    const left = Math.min(Math.max(0, b.left - box.left + b.width / 2 - tip.offsetWidth / 2), box.width - tip.offsetWidth);
                    tip.style.left = left + 'px';
                    tip.style.top = '-8px';
                };
                chart.querySelectorAll('[data-tip]').forEach((bar) => {
                    bar.addEventListener('mouseenter', () => show(bar));
                    bar.addEventListener('focus', () => show(bar));
                });
                chart.addEventListener('mouseleave', () => tip.classList.add('hidden'));
                chart.addEventListener('focusout', () => tip.classList.add('hidden'));
            })();
        </script>
    @endpush
</x-layouts.admin>
