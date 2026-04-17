<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

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

        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%2318181b'/%3E%3Ctext x='16' y='23' font-family='ui-sans-serif%2Csans-serif' font-weight='700' font-size='20' fill='white' text-anchor='middle'%3ED%3C/text%3E%3C/svg%3E">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Google Analytics -->
        @if(env('GA_ID'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_ID') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ env('GA_ID') }}');
        </script>
        @endif

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
