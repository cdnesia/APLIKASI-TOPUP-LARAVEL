@props(['dark' => false, 'size' => 'base'])
@php
    // Wordmark dipecah di titik pertama: "elhazmi" + ".com"
    $name = \App\Models\Setting::get('site_name');
    [$word, $tld] = str_contains($name, '.') ? [strstr($name, '.', true), strstr($name, '.')] : [$name, ''];
    $mark = $size === 'lg' ? 'h-10 w-10 text-lg' : 'h-8 w-8 text-base';
@endphp
@php
    // Logo unggahan admin; versi gelap jatuh ke logo utama bila belum diunggah
    $image = $dark
        ? (\App\Models\Setting::imageUrl('logo_dark') ?? \App\Models\Setting::imageUrl('logo'))
        : \App\Models\Setting::imageUrl('logo');
@endphp
@if ($image)
    <img src="{{ $image }}" alt="{{ $name }}" {{ $attributes->merge(['class' => ($size === 'lg' ? 'h-11' : 'h-9').' w-auto max-w-[200px] object-contain']) }}>
@else
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="{{ $mark }} relative flex items-center justify-center rounded-[10px] bg-primary font-bold text-white shadow-sm shadow-primary/30">
        {{ mb_strtolower(mb_substr($word, 0, 1)) }}
        <span class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-accent ring-2 {{ $dark ? 'ring-ink' : 'ring-surface' }}"></span>
    </span>
    <span class="text-[17px] leading-none font-bold tracking-tight {{ $dark ? 'text-white' : 'text-text' }}">{{ $word }}<span class="{{ $dark ? 'text-slate-400' : 'text-text-muted' }} font-medium">{{ $tld }}</span></span>
</span>
@endif
