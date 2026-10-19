<x-layouts.admin title="Transaksi">
    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="q" class="label">Cari</label>
            <input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Invoice, ID tujuan, atau WhatsApp">
        </div>
        <div>
            <label for="payment_status" class="label">Status Pembayaran</label>
            <select id="payment_status" name="payment_status" class="input">
                <option value="">Semua</option>
                @foreach ($paymentStatuses as $status)
                    <option value="{{ $status->value }}" @selected(request('payment_status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="label">Status Transaksi</label>
            <select id="status" name="status" class="input">
                <option value="">Semua</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-4">
            <button class="btn-primary">Terapkan Filter</button>
            <a href="{{ route('admin.orders.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <section class="card">
        @include('admin.orders.table')
        @if ($orders->hasPages())
            <div class="px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
