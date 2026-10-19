<x-layouts.admin title="Metode Pembayaran">
    {{-- Pembayaran otomatis: cukup centang metode yang aktif di proyek Duitku --}}
    <section class="card mb-6">
        <header class="flex flex-col gap-2 border-b border-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3>Pembayaran Otomatis (Duitku)</h3>
                <p class="mt-1 text-xs text-text-muted">Centang metode yang ingin ditampilkan ke pembeli. Nama, jenis, dan logo terisi otomatis. Lunas terdeteksi sendiri tanpa unggah bukti.</p>
            </div>
            @if ($gatewayConfigured)
                <a href="{{ route('admin.payment-methods.index', ['refresh' => 1]) }}" class="btn-ghost btn-sm shrink-0">Muat ulang dari Duitku</a>
            @endif
        </header>

        @if (! $gatewayConfigured)
            <div class="px-5 py-8 text-center">
                <p class="font-medium">Duitku belum terhubung</p>
                <p class="mt-1 text-xs text-text-muted">Isi <code>DUITKU_MERCHANT_CODE</code> dan <code>DUITKU_API_KEY</code> di file <code>.env</code>, lalu muat ulang halaman ini.</p>
                <a href="{{ route('admin.payment-methods.create') }}" class="btn-secondary btn-sm mt-4">Tambah metode manual saja</a>
            </div>
        @elseif ($gatewayError)
            <div class="px-5 py-8 text-center">
                <p class="font-medium text-danger">Gagal memuat daftar metode dari Duitku</p>
                <p class="mt-1 text-xs text-text-muted">{{ $gatewayError }}</p>
                <a href="{{ route('admin.payment-methods.index', ['refresh' => 1]) }}" class="btn-secondary btn-sm mt-4">Coba lagi</a>
            </div>
            @push('scripts')
                <script>document.addEventListener('DOMContentLoaded', () => showToast('✕ Gagal memuat data. Periksa koneksi Anda.', 'error'));</script>
            @endpush
        @elseif (empty($gateway) && $orphans->isEmpty())
            <div class="px-5 py-8 text-center">
                <p class="font-medium">Belum ada metode aktif di proyek Duitku Anda</p>
                <p class="mt-1 text-xs text-text-muted">Aktifkan metode pembayaran di dashboard Duitku (menu Proyek), lalu muat ulang.</p>
                <a href="{{ route('admin.payment-methods.index', ['refresh' => 1]) }}" class="btn-secondary btn-sm mt-4">Muat ulang</a>
            </div>
        @else
            <form action="{{ route('admin.payment-methods.activate') }}" method="POST" class="p-5">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($gateway as $item)
                        @php
                            $saved = $methods->firstWhere('gateway_code', $item['paymentMethod']);
                            $fee = (int) ($item['totalFee'] ?? 0);
                        @endphp
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-border p-3 transition hover:border-primary/50 has-[:checked]:border-primary has-[:checked]:bg-primary-light">
                            <input type="checkbox" name="codes[]" value="{{ $item['paymentMethod'] }}" @checked($saved?->is_active) class="rounded border-border-dark text-primary">
                            <img src="{{ $saved?->logoUrl() ?? $item['paymentImage'] }}" alt="" class="h-8 w-12 shrink-0 rounded border border-border bg-white object-contain p-0.5" loading="lazy">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $saved?->name ?? (\App\Models\PaymentMethod::GATEWAY_CODES[$item['paymentMethod']] ?? \Illuminate\Support\Str::title($item['paymentName'])) }}</p>
                                <p class="text-xs text-text-muted">{{ $item['paymentMethod'] }} · {{ $fee ? 'Biaya Duitku '.rupiah($fee) : 'Tanpa biaya Duitku' }}</p>
                            </div>
                        </label>
                    @endforeach
                    @foreach ($orphans as $method)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-border-dark p-3 has-[:checked]:border-warning">
                            <input type="checkbox" name="codes[]" value="{{ $method->gateway_code }}" @checked($method->is_active) class="rounded border-border-dark text-primary">
                            @if ($method->logoUrl())
                                <img src="{{ $method->logoUrl() }}" alt="" class="h-8 w-12 shrink-0 rounded border border-border bg-white object-contain p-0.5">
                            @endif
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $method->name }}</p>
                                <p class="text-xs text-warning">{{ $method->gateway_code }} · tidak ditemukan di proyek Duitku</p>
                            </div>
                        </label>
                    @endforeach
                </div>
                <div class="mt-4 flex flex-col gap-2 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-text-muted">Metode yang tidak dicentang dinonaktifkan (tidak dihapus). Biaya tambahan ke pembeli bisa diatur lewat tombol Edit di tabel bawah.</p>
                    <button class="btn-primary shrink-0">Simpan Pilihan</button>
                </div>
            </form>
        @endif
    </section>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3>Semua Metode</h3>
            <p class="mt-1 text-xs text-text-muted">Metode manual (transfer + unggah bukti) ditambahkan lewat form karena butuh nomor rekening.</p>
        </div>
        <a href="{{ route('admin.payment-methods.create') }}" class="btn-primary shrink-0">+ Tambah Metode Manual</a>
    </div>

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Nama</th><th>Jenis</th><th>Rekening / Akun</th><th>Biaya</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($methods as $method)
                        <tr class="hover:bg-slate-50">
                            <td class="font-medium">
                                <div class="flex items-center gap-3">
                                    @if ($method->logoUrl())
                                        <img src="{{ $method->logoUrl() }}" alt="" class="h-8 w-12 shrink-0 rounded border border-border bg-white object-contain p-0.5">
                                    @endif
                                    {{ $method->name }}
                                </div>
                            </td>
                            <td>{{ $method->typeLabel() }}</td>
                            <td>
                                @if ($method->isAutomatic())
                                    <span class="badge bg-blue-100 text-blue-800">Otomatis · Duitku {{ $method->gateway_code }}</span>
                                @elseif ($method->account_number)
                                    {{ $method->account_number }}<p class="text-xs text-text-muted">a.n. {{ $method->account_name }}</p>
                                @else
                                    <span class="text-text-muted">Gambar QRIS</span>
                                @endif
                            </td>
                            <td>{{ rupiah($method->fee) }}</td>
                            <td>
                                <span class="badge {{ $method->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">{{ $method->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                @if ($method->isAutomatic() && $onlineCodes !== null)
                                    @if (in_array($method->gateway_code, $onlineCodes, true))
                                        <span class="badge mt-1 bg-green-50 text-success">● Online</span>
                                    @else
                                        <span class="badge mt-1 bg-red-50 text-danger" title="Tidak tersedia di proyek Duitku saat ini">● Offline</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.payment-methods.edit', $method) }}" class="btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('admin.payment-methods.destroy', $method) }}" method="POST" class="inline" data-confirm="Hapus metode {{ $method->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost btn-sm text-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <p class="font-medium">Belum ada metode pembayaran</p>
                                <p class="mt-1 text-xs text-text-muted">Centang metode Duitku di atas, atau tambahkan rekening bank / QRIS manual.</p>
                                <a href="{{ route('admin.payment-methods.create') }}" class="btn-secondary btn-sm mt-3">Tambah Metode Manual</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
