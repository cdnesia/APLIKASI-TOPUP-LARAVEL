<x-layouts.admin :title="'Edit Brand: '.$brand->name">
    <a href="{{ route('admin.brands.index') }}" class="mb-4 inline-block text-sm text-text-muted hover:text-primary">← Kembali</a>

    <form action="{{ route('admin.brands.update', $brand->id) }}" method="POST" enctype="multipart/form-data" class="card max-w-2xl space-y-5 p-5 sm:p-6">
        @csrf @method('PUT')
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <p class="label">Logo / Thumbnail</p>
                <div class="flex items-center gap-4">
                    <x-brand-logo :brand="$brand" size="h-20 w-20" />
                    <div class="min-w-0 flex-1">
                        <label for="image" class="sr-only">Unggah logo</label>
                        <input id="image" name="image" type="file" accept="image/*" data-preview="preview-image" class="input text-xs @error('image') input-error @enderror">
                        @error('image') <p class="field-error">{{ $message }}</p> @else <p class="hint">Persegi 1:1, mis. 256×256 px, maks. 2 MB.</p> @enderror
                        @if ($brand->image)
                            <label class="mt-2 flex items-center gap-2 text-xs text-danger"><input type="checkbox" name="remove_image" value="1" class="rounded border-border-dark"> Hapus logo</label>
                        @endif
                    </div>
                </div>
                <img id="preview-image" alt="Pratinjau logo" class="mt-3 hidden h-20 w-20 rounded-xl object-cover">
            </div>
            <div>
                <p class="label">Banner Halaman Order</p>
                <div class="aspect-[3/1] overflow-hidden rounded-xl border border-dashed border-border-dark bg-slate-50">
                    @if ($brand->bannerUrl())
                        <img id="preview-banner" src="{{ $brand->bannerUrl() }}" alt="Banner {{ $brand->name }}" class="h-full w-full object-cover">
                    @else
                        <img id="preview-banner" alt="Pratinjau banner" class="hidden h-full w-full object-cover">
                        <div id="preview-banner-empty" class="flex h-full items-center justify-center gap-2 text-xs text-text-muted"><x-icon name="photo" /> Belum ada banner</div>
                    @endif
                </div>
                <label for="banner" class="sr-only">Unggah banner</label>
                <input id="banner" name="banner" type="file" accept="image/*" data-preview="preview-banner" class="input mt-2 text-xs @error('banner') input-error @enderror">
                @error('banner') <p class="field-error">{{ $message }}</p> @else <p class="hint">Lebar, mis. 1200×400 px, maks. 4 MB.</p> @enderror
                @if ($brand->banner)
                    <label class="mt-2 flex items-center gap-2 text-xs text-danger"><input type="checkbox" name="remove_banner" value="1" class="rounded border-border-dark"> Hapus banner</label>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="w-32 shrink-0">
                <p class="label">Cover Katalog</p>
                <div class="aspect-[3/4] overflow-hidden rounded-xl border border-dashed border-border-dark bg-slate-50">
                    <img id="preview-cover" src="{{ $brand->coverUrl() }}" alt="Pratinjau cover" @class(['h-full w-full object-cover', 'hidden' => ! $brand->coverUrl()])>
                    @unless ($brand->coverUrl())
                        <div id="preview-cover-empty" class="flex h-full items-center justify-center text-text-muted"><x-icon name="photo" /></div>
                    @endunless
                </div>
            </div>
            <div class="flex-1">
                <label for="cover" class="label">Unggah cover potret</label>
                <input id="cover" name="cover" type="file" accept="image/*" data-preview="preview-cover" class="input text-xs @error('cover') input-error @enderror">
                @error('cover') <p class="field-error">{{ $message }}</p> @else <p class="hint">Rasio 3:4, mis. 600×800 px, maks. 4 MB. Dipakai sebagai kartu besar di katalog beranda. Bila kosong, kartu memakai logo.</p> @enderror
                @if ($brand->cover)
                    <label class="mt-2 flex items-center gap-2 text-xs text-danger"><input type="checkbox" name="remove_cover" value="1" class="rounded border-border-dark"> Hapus cover</label>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="label">Nama Tampilan</label>
                <input id="name" name="name" value="{{ old('name', $brand->name) }}" required class="input @error('name') input-error @enderror">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="publisher" class="label">Publisher / Penyedia</label>
                <input id="publisher" name="publisher" value="{{ old('publisher', $brand->publisher) }}" class="input" placeholder="Mis. Moonton, Garena">
                <p class="hint">Tampil di bawah nama pada kartu produk.</p>
            </div>
            <div>
                <label for="sort_order" class="label">Urutan</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $brand->sort_order) }}" class="input">
                <p class="hint">Angka kecil tampil lebih dulu.</p>
            </div>
            <div>
                <label for="field_label" class="label">Label Input Utama</label>
                <input id="field_label" name="field_label" value="{{ old('field_label', $brand->field_label) }}" required class="input @error('field_label') input-error @enderror">
                @error('field_label') <p class="field-error">{{ $message }}</p> @else <p class="hint">Mis. User ID, Nomor HP, Nomor Meter</p> @enderror
            </div>
            <div>
                <label for="zone_label" class="label">Label Input Kedua (opsional)</label>
                <input id="zone_label" name="zone_label" value="{{ old('zone_label', $brand->zone_label) }}" class="input" placeholder="Mis. Zone ID">
                <p class="hint">Isi jika game butuh Zone/Server ID. Nilainya digabung ke ID utama saat dikirim ke Digiflazz.</p>
            </div>
        </div>

        <div>
            <label for="input_hint" class="label">Petunjuk Input</label>
            <input id="input_hint" name="input_hint" value="{{ old('input_hint', $brand->input_hint) }}" class="input" placeholder="Mis. Cek User ID di menu profil game">
        </div>

        <div>
            <label for="description" class="label">Deskripsi</label>
            <textarea id="description" name="description" rows="4" class="input">{{ old('description', $brand->description) }}</textarea>
        </div>

        <div class="space-y-3 rounded-xl border border-border bg-bg/50 p-4">
            <div>
                <h3 class="text-base">SEO Halaman Ini</h3>
                <p class="mt-1 text-xs text-text-muted">Kosongkan untuk memakai template di <a href="{{ route('admin.settings.edit') }}" class="text-primary hover:underline">Pengaturan → SEO</a>. Variabel <code>{nama}</code> <code>{harga_mulai}</code> dll. tetap bisa dipakai.</p>
            </div>
            @php($minPrice = $brand->products()->available()->min('selling_price'))
            @php($vars = ['{nama}' => $brand->name, '{kategori}' => $brand->category, '{publisher}' => $brand->publisher ?: $brand->name, '{harga_mulai}' => $minPrice ? rupiah($minPrice) : 'Rp…', '{situs}' => \App\Models\Setting::get('site_name')])
            <x-seo-fields title-name="seo_title" desc-name="seo_description"
                :title-value="old('seo_title', $brand->seo_title)" :desc-value="old('seo_description', $brand->seo_description)"
                :title-placeholder="strtr(\App\Models\Setting::get('seo_brand_title'), $vars)" :desc-placeholder="strtr(\App\Models\Setting::get('seo_brand_description'), $vars)"
                :url="route('brands.show', $brand)" :suffix="\App\Models\Setting::get('site_name')" :example="$vars" />
        </div>

        <div class="flex flex-wrap gap-6">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active)) class="rounded border-border-dark text-primary"> Tampilkan di website</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_popular" value="1" @checked(old('is_popular', $brand->is_popular)) class="rounded border-border-dark text-primary"> Tandai populer</label>
        </div>

        <div class="flex gap-2 border-t border-border pt-4">
            <button class="btn-primary">Simpan Perubahan</button>
            <a href="{{ route('admin.brands.index') }}" class="btn-ghost">Batal</a>
        </div>
    </form>

    <form action="{{ route('admin.brands.product-image', $brand->id) }}" method="POST" enctype="multipart/form-data" class="card mt-6 max-w-2xl space-y-3 p-5 sm:p-6"
        data-confirm="Terapkan gambar ini ke semua nominal {{ $brand->name }}? Gambar nominal sebelumnya akan diganti.">
        @csrf
        <h3 class="text-base">Gambar untuk Semua Nominal</h3>
        <p class="text-text-muted">Mis. ikon diamond untuk semua nominal Mobile Legends. Gambar per nominal juga bisa diatur di halaman <a href="{{ route('admin.products.index', ['brand_id' => $brand->id]) }}" class="text-primary hover:underline">Produk & Harga</a>. Jika kosong, nominal memakai logo brand.</p>
        <div class="flex flex-col gap-2 sm:flex-row">
            <label for="product_image" class="sr-only">Gambar nominal</label>
            <input id="product_image" name="image" type="file" accept="image/*" required class="input text-xs">
            <button class="btn-secondary shrink-0">Terapkan ke {{ $brand->products()->count() }} Nominal</button>
        </div>
    </form>

    @push('scripts')
        <script>
            // Pratinjau gambar sebelum disimpan
            document.querySelectorAll('input[data-preview]').forEach((input) => input.addEventListener('change', () => {
                const file = input.files[0];
                const img = document.getElementById(input.dataset.preview);
                if (!file || !img) return;
                img.src = URL.createObjectURL(file);
                img.classList.remove('hidden');
                document.getElementById(input.dataset.preview + '-empty')?.classList.add('hidden');
            }));
        </script>
    @endpush
</x-layouts.admin>
