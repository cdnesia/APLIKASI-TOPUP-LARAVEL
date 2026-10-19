@props(['brand'])

{{-- Kartu katalog potret 3:4 — memakai cover bila ada, selain itu logo di atas latar lembut --}}
<a href="{{ route('brands.show', $brand) }}" {{ $attributes->merge(['class' => 'group block overflow-hidden rounded-2xl border border-border bg-surface transition duration-200 hover:-translate-y-1 hover:border-primary/40 hover:shadow-lift']) }}>
    <div class="relative aspect-[3/4] overflow-hidden bg-primary-light/60">
        @if ($brand->coverUrl())
            <img src="{{ $brand->coverUrl() }}" alt="{{ $brand->name }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-full flex-col items-center justify-center gap-3 p-4">
                <x-brand-logo :brand="$brand" size="h-20 w-20" class="shadow-soft transition duration-300 group-hover:scale-105" />
            </div>
            <x-icon :name="$brand->categoryIcon()" class="absolute -right-4 -bottom-4 h-24 w-24 text-primary/[0.07]" />
        @endif
        @if ($brand->is_popular)
            <span class="absolute top-2 left-2 rounded-full bg-accent px-2 py-0.5 text-[10px] font-semibold text-white">Populer</span>
        @endif
    </div>
    <div class="px-3 py-2.5">
        <p class="truncate text-sm font-semibold">{{ $brand->name }}</p>
        <p class="truncate text-xs text-text-muted">{{ $brand->publisher ?: $brand->category }}</p>
    </div>
</a>
