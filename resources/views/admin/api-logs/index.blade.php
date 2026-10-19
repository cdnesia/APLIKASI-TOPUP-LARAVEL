<x-layouts.admin title="Log API" subtitle="Riwayat komunikasi dengan Digiflazz & Duitku (disimpan 30 hari)">
    @if ($failedToday)
        <a href="{{ route('admin.api-logs.index', ['result' => 'failed']) }}" class="mb-4 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 hover:bg-red-100">
            <span class="font-semibold">{{ $failedToday }} panggilan API gagal hari ini.</span> Klik untuk melihat →
        </a>
    @endif

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="q" class="label">Cari</label>
            <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Invoice, endpoint, atau pesan">
        </div>
        <div>
            <label for="provider" class="label">Layanan</label>
            <select id="provider" name="provider" class="input">
                <option value="">Semua</option>
                @foreach (\App\Models\ApiLog::PROVIDERS as $value => $label)
                    <option value="{{ $value }}" @selected(request('provider') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="result" class="label">Hasil</label>
            <select id="result" name="result" class="input">
                <option value="">Semua</option>
                <option value="failed" @selected(request('result') === 'failed')>Gagal saja</option>
                <option value="success" @selected(request('result') === 'success')>Berhasil saja</option>
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-4">
            <button class="btn-primary">Terapkan Filter</button>
            <a href="{{ route('admin.api-logs.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <section class="card overflow-hidden">
        <p class="border-b border-border px-4 py-2 text-xs text-text-muted">Klik baris untuk melihat isi request & respons. API key dan signature disamarkan (***).</p>
        @include('admin.api-logs.list', ['logs' => $logs])
        @if ($logs->hasPages())
            <div class="border-t border-border px-4 py-3">{{ $logs->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
