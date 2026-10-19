{{--
    Meta SEO & data terstruktur untuk halaman publik.
    Variabel: $pageTitle, $metaDescription, $image (URL absolut|null), $noindex (bool), $schema (array JSON-LD tambahan)
--}}
@php
    use App\Models\Setting;
    $siteName = Setting::get('site_name');
    $canonical = url()->current();
    $ogImage = $image ?: (Setting::imageUrl('og_image') ?? asset('og-image.png'));
    $wa = Setting::get('whatsapp_admin');

    // Organization + WebSite (dengan kotak pencarian) — membantu Google menampilkan nama & pencarian situs
    $graph = [
        [
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => $siteName,
            'url' => url('/'),
            'logo' => Setting::imageUrl('logo') ?? asset('icon-512.png'),
            'contactPoint' => array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'telephone' => $wa ? "+{$wa}" : null,
                'email' => Setting::get('contact_email') ?: null,
                'availableLanguage' => 'Indonesian',
            ]),
            'sameAs' => array_values(array_filter([Setting::get('instagram') ? 'https://instagram.com/'.Setting::get('instagram') : null])),
        ],
        [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => $siteName,
            'url' => url('/'),
            'inLanguage' => 'id-ID',
            'publisher' => ['@id' => url('/').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('home').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ],
        ...($schema ?? []),
    ];
@endphp
<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ $noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' }}">
@if ($verification = Setting::get('google_site_verification'))
<meta name="google-site-verification" content="{{ $verification }}">
@endif

{{-- Open Graph: pratinjau saat link dibagikan di WhatsApp, Facebook, Telegram --}}
<meta property="og:type" content="website">
<meta property="og:locale" content="id_ID">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

@unless ($noindex)
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endunless
