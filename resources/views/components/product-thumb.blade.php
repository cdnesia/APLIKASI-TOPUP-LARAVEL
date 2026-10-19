@props(['product', 'size' => 'h-10 w-10'])

@if ($url = $product->imageUrl())
    <img src="{{ $url }}" alt="" loading="lazy" {{ $attributes->merge(['class' => "$size shrink-0 rounded-lg bg-surface object-contain"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$size flex shrink-0 items-center justify-center rounded-lg bg-primary-light text-primary"]) }}>
        <x-icon :name="\App\Models\Brand::categoryIconFor($product->category)" class="h-1/2 w-1/2" />
    </span>
@endif
