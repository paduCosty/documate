<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="google-site-verification" content="Px390XkFbxG-W51VRk9Y64jE0m-NkNE4nrpjgr3kj6o" />

        <title inertia>{{ config('app.name', 'Documate') }}</title>

        <!-- Default SEO fallback (overridden per-page by Inertia Head component) -->
        <meta name="description" content="Free online PDF tools — merge, compress, split, and convert PDF files in seconds. No signup required. Files auto-deleted in 24 hours.">
        <meta property="og:site_name" content="Documate">
        <meta property="og:title" content="Documate — Free Online PDF Tools">
        <meta property="og:description" content="Free online PDF tools — merge, compress, split, and convert PDF files in seconds. No signup required. Files auto-deleted in 24 hours.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta property="og:image" content="{{ config('app.url') }}/og-image.png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Documate — Free Online PDF Tools">
        <meta name="twitter:description" content="Free online PDF tools — merge, compress, split, and convert PDF files in seconds. No signup required. Files auto-deleted in 24 hours.">
        <meta name="twitter:image" content="{{ config('app.url') }}/og-image.png">

        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="shortcut icon" href="/favicon.ico">

        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Documate', 'url' => config('app.url')]) !!}</script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
