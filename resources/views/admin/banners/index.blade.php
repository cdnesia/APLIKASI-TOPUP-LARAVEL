<x-layouts.admin title="Banner Promo" subtitle="Slide yang tampil di bagian atas beranda">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-text-muted">Ukuran disarankan 1600×600 px (rasio 8:3). Banner aktif tampil berurutan dan bergeser otomatis.</p>
        <a href="{{ route('admin.banners.create') }}" class="btn-primary shrink-0">+ Tambah Banner</a>
    </div>

    @if ($banners->isEmpty())
        <div class="card flex flex-col items-center px-6 py-16 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-light text-primary"><x-icon name="photo" class="h-7 w-7" /></span>
            <h3 class="mt-4 text-base">Belum ada banner</h3>
            <p class="mt-1 text-text-muted">Tanpa banner, beranda menampilkan sapaan teks bawaan.</p>
            <a href="{{ route('admin.banners.create') }}" class="btn-primary mt-5">Tambah Banner Pertama</a>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($banners as $banner)
                <article class="card overflow-hidden">
                    <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="aspect-[8/3] w-full object-cover">
                    <div class="flex items-start justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $banner->title }}</p>
                            <p class="mt-0.5 truncate text-xs text-text-muted">{{ $banner->link_url ?: 'Tanpa tautan' }} · urutan {{ $banner->sort_order }}</p>
                        </div>
                        <span class="badge {{ $banner->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">{{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    <div class="flex gap-2 border-t border-border px-4 py-3">
                        <a href="{{ route('admin.banners.edit', $banner) }}" class="btn-secondary btn-sm">Edit</a>
                        <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" data-confirm="Hapus banner {{ $banner->title }}?">
                            @csrf @method('DELETE')
                            <button class="btn-ghost btn-sm text-danger">Hapus</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.admin>
