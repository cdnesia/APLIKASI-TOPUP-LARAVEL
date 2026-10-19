@props(['title', 'subtitle' => null, 'eyebrow' => null])

<section class="mx-auto max-w-6xl px-4 pt-10 sm:pt-14">
    <nav class="mb-4 flex items-center gap-1.5 text-xs text-text-muted" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-text">Beranda</a>
        <x-icon name="chevron-right" class="h-3 w-3" />
        <span class="text-text">{{ $title }}</span>
    </nav>
    @if ($eyebrow)
        <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    <h1 class="mt-1 text-3xl sm:text-4xl">{{ $title }}</h1>
    @if ($subtitle)
        <p class="mt-3 max-w-2xl leading-relaxed text-text-muted">{{ $subtitle }}</p>
    @endif
    {{ $slot }}
</section>
