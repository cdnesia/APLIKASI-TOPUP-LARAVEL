<x-layouts.admin :title="$method->exists ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran'">
    <a href="{{ route('admin.payment-methods.index') }}" class="mb-4 inline-block text-sm text-text-muted hover:text-primary">← Kembali</a>

    <form action="{{ $method->exists ? route('admin.payment-methods.update', $method) : route('admin.payment-methods.store') }}"
        method="POST" enctype="multipart/form-data" class="card max-w-2xl space-y-5 p-5 sm:p-6">
        @csrf
        @if ($method->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="label">Nama</label>
                <input id="name" name="name" value="{{ old('name', $method->name) }}" required class="input @error('name') input-error @enderror" placeholder="Mis. Transfer BCA">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="type" class="label">Jenis</label>
                <select id="type" name="type" class="input">
                    @foreach (\App\Models\PaymentMethod::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $method->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($manualOnly ?? false)
            <input type="hidden" id="gateway_code" name="gateway_code" value="">
            <p class="hint sm:col-span-2">Untuk pembayaran otomatis (VA, e-wallet, QRIS Duitku) tidak perlu form ini — cukup centang di halaman <a href="{{ route('admin.payment-methods.index') }}" class="text-primary hover:underline">Metode Pembayaran</a>.</p>
            @else
            <div class="sm:col-span-2">
                <label for="gateway_code" class="label">Proses Pembayaran</label>
                <select id="gateway_code" name="gateway_code" class="input @error('gateway_code') input-error @enderror">
                    <option value="">Manual — pembeli transfer & unggah bukti, admin konfirmasi</option>
                    <optgroup label="Otomatis via Duitku (lunas terdeteksi sendiri)">
                        @foreach (\App\Models\PaymentMethod::GATEWAY_CODES + array_filter([$method->gateway_code => $method->gateway_code]) as $code => $label)
                            <option value="{{ $code }}" @selected(old('gateway_code', $method->gateway_code) === $code)>{{ $label }} ({{ $code }})</option>
                        @endforeach
                    </optgroup>
                </select>
                @error('gateway_code') <p class="field-error">{{ $message }}</p> @else <p class="hint">Pastikan metode ini sudah aktif di dashboard Duitku pada proyek Anda.</p> @enderror
            </div>
            @endif
            <div data-account>
                <label for="account_number" class="label">Nomor Rekening / Akun</label>
                <input id="account_number" name="account_number" value="{{ old('account_number', $method->account_number) }}" class="input @error('account_number') input-error @enderror">
                @error('account_number') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div data-account>
                <label for="account_name" class="label">Atas Nama</label>
                <input id="account_name" name="account_name" value="{{ old('account_name', $method->account_name) }}" class="input @error('account_name') input-error @enderror">
                @error('account_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="image" class="label" id="image-label">Logo / Gambar QRIS</label>
            @if ($method->imageUrl())
                <img src="{{ $method->imageUrl() }}" alt="" class="mb-2 h-24 rounded border border-border object-contain">
            @endif
            <input id="image" name="image" type="file" accept="image/*" class="input @error('image') input-error @enderror">
            @error('image') <p class="field-error">{{ $message }}</p> @else <p class="hint">Untuk QRIS manual, unggah gambar QRIS statis. Untuk metode otomatis cukup logo (opsional).</p> @enderror
        </div>

        <div>
            <label for="instructions" class="label">Instruksi Pembayaran (opsional)</label>
            <textarea id="instructions" name="instructions" rows="3" class="input" placeholder="Mis. Transfer via m-BCA / ATM BCA, lalu unggah bukti di halaman invoice.">{{ old('instructions', $method->instructions) }}</textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="fee" class="label">Biaya Tambahan (Rp)</label>
                <input id="fee" name="fee" type="number" min="0" value="{{ old('fee', $method->fee ?? 0) }}" class="input">
            </div>
            <div>
                <label for="sort_order" class="label">Urutan</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="input">
            </div>
        </div>

        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $method->is_active)) class="rounded border-border-dark text-primary"> Aktif</label>

        <div class="flex gap-2 border-t border-border pt-4">
            <button class="btn-primary">{{ $method->exists ? 'Simpan Perubahan' : 'Tambah Metode' }}</button>
            <a href="{{ route('admin.payment-methods.index') }}" class="btn-ghost">Batal</a>
        </div>
    </form>

    @push('scripts')
        <script>
            // Sembunyikan field rekening untuk QRIS dan metode otomatis
            (() => {
                const type = document.getElementById('type');
                const gateway = document.getElementById('gateway_code');
                const sync = () => document.querySelectorAll('[data-account]').forEach((el) => el.classList.toggle('hidden', type.value === 'qris' || gateway.value !== ''));
                type.addEventListener('change', sync);
                gateway.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endpush
</x-layouts.admin>
