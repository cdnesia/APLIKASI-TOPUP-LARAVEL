<x-layouts.admin :title="$banner->exists ? 'Edit Banner' : 'Tambah Banner'">
    <a href="{{ route('admin.banners.index') }}" class="mb-4 inline-block text-sm text-text-muted hover:text-primary">← Kembali</a>

    <form action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="card max-w-3xl space-y-5 p-5 sm:p-6">
        @csrf
        @if ($banner->exists) @method('PUT') @endif

        <div>
            <p class="label">Gambar Banner</p>
            <div class="aspect-[8/3] overflow-hidden rounded-xl border border-dashed border-border-dark bg-slate-50">
                <img id="preview-image" src="{{ $banner->exists ? $banner->imageUrl() : '' }}" alt="Pratinjau banner" @class(['h-full w-full object-cover', 'hidden' => ! $banner->exists])>
                @unless ($banner->exists)
                    <div id="preview-image-empty" class="flex h-full items-center justify-center gap-2 text-text-muted"><x-icon name="photo" /> Pratinjau muncul di sini</div>
                @endunless
            </div>
            <label for="image" class="sr-only">Unggah gambar</label>
            <input id="image" name="image" type="file" accept="image/*" data-preview="preview-image" @required(! $banner->exists) class="input mt-2 @error('image') input-error @enderror">
            @error('image') <p class="field-error">{{ $message }}</p> @else <p class="hint">1600×600 px (8:3), JPG/PNG/WebP, maks. 4 MB.</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="title" class="label">Judul (untuk teks alternatif)</label>
                <input id="title" name="title" value="{{ old('title', $banner->title) }}" required class="input @error('title') input-error @enderror" placeholder="Mis. Promo Diamond Akhir Bulan">
                @error('title') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="sort_order" class="label">Urutan</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" class="input">
            </div>
        </div>
        <div>
            <label for="link_url" class="label">Tautan saat diklik (opsional)</label>
            <input id="link_url" name="link_url" value="{{ old('link_url', $banner->link_url) }}" class="input @error('link_url') input-error @enderror" placeholder="/topup/mobile-legends-games atau https://…">
            @error('link_url') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active)) class="rounded border-border-dark text-primary"> Tampilkan di beranda</label>

        <div class="flex gap-2 border-t border-border pt-4">
            <button class="btn-primary">{{ $banner->exists ? 'Simpan Perubahan' : 'Tambah Banner' }}</button>
            <a href="{{ route('admin.banners.index') }}" class="btn-ghost">Batal</a>
        </div>
    </form>

    @push('scripts')
        <script>
            document.querySelectorAll('input[data-preview]').forEach((input) => input.addEventListener('change', () => {
                const file = input.files[0], img = document.getElementById(input.dataset.preview);
                if (!file || !img) return;
                img.src = URL.createObjectURL(file);
                img.classList.remove('hidden');
                document.getElementById(input.dataset.preview + '-empty')?.classList.add('hidden');
            }));
        </script>
    @endpush
</x-layouts.admin>
