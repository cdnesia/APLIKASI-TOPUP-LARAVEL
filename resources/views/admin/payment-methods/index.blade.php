<x-layouts.admin title="Metode Pembayaran">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-text-muted">Manual: pembeli transfer lalu unggah bukti. Otomatis: dibayar via Duitku dan langsung diproses.</p>
        <a href="{{ route('admin.payment-methods.create') }}" class="btn-primary shrink-0">+ Tambah Metode</a>
    </div>

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Nama</th><th>Jenis</th><th>Rekening / Akun</th><th>Biaya</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($methods as $method)
                        <tr class="hover:bg-slate-50">
                            <td class="font-medium">{{ $method->name }}</td>
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
                            <td><span class="badge {{ $method->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">{{ $method->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
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
                                <p class="mt-1 text-xs text-text-muted">Tambahkan rekening bank, e-wallet, atau QRIS agar pembeli bisa membayar.</p>
                                <a href="{{ route('admin.payment-methods.create') }}" class="btn-primary btn-sm mt-3">Tambah Metode Pertama</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
