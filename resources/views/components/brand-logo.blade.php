@props(['brand', 'size' => 'h-14 w-14'])

@if ($brand->imageUrl())
    <img src="{{ $brand->imageUrl() }}" alt="Logo {{ $brand->name }}" loading="lazy"
        {{ $attributes->merge(['class' => "$size shrink-0 rounded-xl bg-surface object-cover"]) }}>
@else
    {{-- Thumbnail bawaan: ikon kategori, dipakai sampai admin mengunggah logo --}}
    <div role="img" aria-label="{{ $brand->name }}" {{ $attributes->merge(['class' => "$size relative flex shrink-0 items-center justify-center overflow-hidden rounded-xl bg-primary-light text-primary"]) }}>
        <x-icon :name="$brand->categoryIcon()" class="h-1/2 w-1/2" />
        <span class="absolute right-1 bottom-1 rounded bg-surface/90 px-1 text-[9px] leading-tight font-bold">{{ $brand->initials() }}</span>
    </div>
@endif
