<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php
        $pageSeo = data_get($page ?? [], 'props.seo');
        $hasSeo = is_array($pageSeo) && $pageSeo !== [];
        $siteName = config('app.name', 'Fleximo');
        $baseUrl = rtrim((string) config('seo.site.base_url', config('app.url', 'https://example.com')), '/');
        $defaultImage = $baseUrl . '/og-image.svg';
        $defaultDescription = (string) __('common.seo.default_description');
        $titleText = $hasSeo && filled($pageSeo['title'] ?? null)
            ? (str_contains((string) $pageSeo['title'], $siteName) ? (string) $pageSeo['title'] : (string) $pageSeo['title'] . ' | ' . $siteName)
            : $siteName;
        $description = $hasSeo ? (string) ($pageSeo['description'] ?? $defaultDescription) : null;
        $keywords = $hasSeo ? ($pageSeo['keywords'] ?? null) : null;
        $canonical = $hasSeo ? (string) ($pageSeo['canonical'] ?? url()->current()) : null;
        $ogType = $hasSeo ? (string) ($pageSeo['ogType'] ?? 'website') : null;
        $ogImage = $hasSeo ? (string) ($pageSeo['ogImage'] ?? $defaultImage) : null;
        $ogImageAlt = $hasSeo ? (string) ($pageSeo['ogImageAlt'] ?? $siteName) : null;
        $twitterCard = $hasSeo ? (string) ($pageSeo['twitterCard'] ?? 'summary_large_image') : null;
        $robots = $hasSeo
            ? implode(',', [
                !empty($pageSeo['noindex']) ? 'noindex' : 'index',
                !empty($pageSeo['nofollow']) ? 'nofollow' : 'follow',
                'max-snippet:-1',
                'max-image-preview:large',
                'max-video-preview:-1',
            ])
            : null;
        $pageStructuredData = data_get($page ?? [], 'props.structuredData');
        $structuredDataArray = [];

        if (is_array($pageStructuredData)) {
            $structuredDataArray = array_is_list($pageStructuredData) ? $pageStructuredData : [$pageStructuredData];
        }
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="application-name" content="Fleximo">
    <meta name="apple-mobile-web-app-title" content="Fleximo">
    <meta name="theme-color" content="#0f172a">

    <title inertia>{{ $hasSeo ? $titleText : config('app.name', 'Laravel') }}</title>

    @if ($hasSeo)
        <meta name="description" content="{{ $description }}">
        @if (filled($keywords))
            <meta name="keywords" content="{{ $keywords }}">
        @endif
        <meta name="robots" content="{{ $robots }}">
        <meta name="googlebot" content="{{ $robots }}">
        <link rel="canonical" href="{{ $canonical }}">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $titleText }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:alt" content="{{ $ogImageAlt }}">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:site_name" content="Fleximo">
        <meta property="og:locale" content="{{ app()->getLocale() === 'ja' ? 'ja_JP' : 'en_US' }}">
        <meta name="twitter:card" content="{{ $twitterCard }}">
        <meta name="twitter:title" content="{{ $titleText }}">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
        <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">

        @foreach ($structuredDataArray as $structuredData)
            <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
        @endforeach
    @endif

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    <meta name="csp-nonce" content="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">

    {{-- i18n 初期化前のエラー表示などで参照する同期 locale チャネル --}}
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">window.__APP_LOCALE__ = @json(app()->getLocale());</script>

    <!-- Scripts -->
    @routes(nonce: \Illuminate\Support\Facades\Vite::cspNonce())
    @viteReactRefresh
    @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @if ($hasSeo)
        <noscript>
            <main style="margin:0 auto;max-width:56rem;padding:2rem 1.5rem;font-family:system-ui,sans-serif;line-height:1.7;">
                <h1 style="margin:0 0 1rem;font-size:2rem;">{{ $titleText }}</h1>
                <p style="margin:0;color:#334155;">{{ $description }}</p>
            </main>
        </noscript>
    @endif
    @inertia
</body>

</html>
