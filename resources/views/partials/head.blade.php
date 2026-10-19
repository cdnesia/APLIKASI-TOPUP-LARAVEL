<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- Favicon: unggahan admin bila ada; bawaan: SVG untuk browser modern, ICO cadangan, PNG untuk iOS --}}
@if ($favicon = \App\Models\Setting::imageUrl('favicon'))
<link rel="icon" href="{{ $favicon }}">
<link rel="apple-touch-icon" href="{{ $favicon }}">
@else
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@endif
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
