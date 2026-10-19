<x-layouts.admin :title="$flashSale->exists ? 'Edit Flash Sale' : 'Buat Flash Sale'">
    <a href="{{ route('admin.flash-sales.index') }}" class="mb-4 inline-block text-sm text-text-muted hover:text-primary">← Kembali</a>

    <form action="{{ $flashSale->exists ? route('admin.flash-sales.update', $flashSale) : route('admin.flash-sales.store') }}" method="POST" class="card max-w-2xl space-y-5 p-5 sm:p-6">
        @csrf
        @if ($flashSale->exists) @method('PUT') @endif

        <div>
            <label for="product_id" class="label">Produk</label>
            <select id="product_id" name="product_id" required class="input @error('product_id') input-error @enderror">
                <option value="">Pilih produk…</option>
                @foreach ($products as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach ($items as $product)
                            <option value="{{ $product->id }}" data-base="{{ $product->base_price }}" data-sell="{{ $product->selling_price }}" @selected(old('product_id', $flashSale->product_id) == $product->id)>
                                {{ $product->name }} — modal {{ rupiah($product->base_price) }}, jual {{ rupiah($product->selling_price) }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('product_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="price" class="label">Harga Flash Sale (Rp)</label>
                <input id="price" name="price" type="number" min="1" value="{{ old('price', $flashSale->price) }}" required class="input @error('price') input-error @enderror">
                @error('price') <p class="field-error">{{ $message }}</p> @else <p class="hint" id="price-info">Pilih produk untuk melihat modal & laba.</p> @enderror
            </div>
            <div>
                <label for="quota" class="label">Kuota</label>
                <input id="quota" name="quota" type="number" min="1" value="{{ old('quota', $flashSale->quota ?? 10) }}" required class="input @error('quota') input-error @enderror">
                @error('quota') <p class="field-error">{{ $message }}</p> @else <p class="hint">Jumlah maksimal item yang bisa dibeli dengan harga ini.</p> @enderror
            </div>
            <div>
                <label for="starts_at" class="label">Mulai</label>
                <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $flashSale->starts_at?->format('Y-m-d\TH:i')) }}" required class="input @error('starts_at') input-error @enderror">
                @error('starts_at') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ends_at" class="label">Selesai</label>
                <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $flashSale->ends_at?->format('Y-m-d\TH:i')) }}" required class="input @error('ends_at') input-error @enderror">
                @error('ends_at') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $flashSale->is_active)) class="rounded border-border-dark text-primary"> Aktif</label>

        <div class="flex gap-2 border-t border-border pt-4">
            <button class="btn-primary">{{ $flashSale->exists ? 'Simpan Perubahan' : 'Buat Flash Sale' }}</button>
            <a href="{{ route('admin.flash-sales.index') }}" class="btn-ghost">Batal</a>
        </div>
    </form>

    @push('scripts')
        <script>
            // Tampilkan modal, diskon, dan laba/rugi per item secara langsung
            (() => {
                const select = document.getElementById('product_id'), price = document.getElementById('price'), info = document.getElementById('price-info');
                if (!info) return;
                const fmt = (n) => 'Rp' + Math.abs(n).toLocaleString('id-ID');
                const update = () => {
                    const opt = select.selectedOptions[0];
                    if (!opt?.dataset.base) return;
                    const base = +opt.dataset.base, sell = +opt.dataset.sell, p = +price.value || 0;
                    const profit = p - base, disc = sell ? Math.round((1 - p / sell) * 100) : 0;
                    info.textContent = `Normal ${fmt(sell)} · diskon ${disc}% · ` + (profit >= 0 ? `laba ${fmt(profit)}/item` : `RUGI ${fmt(profit)}/item`);
                    info.classList.toggle('text-danger', profit < 0);
                };
                select.addEventListener('change', update);
                price.addEventListener('input', update);
                update();
            })();
        </script>
    @endpush
</x-layouts.admin>
