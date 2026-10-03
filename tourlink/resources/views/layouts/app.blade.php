<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('localization.languages.'.app()->getLocale().'.direction', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="view-transition" content="same-origin">
    <meta name="description" content="@yield('meta_description', 'Discover trips, hire verified vehicles and travel Kenya with confidence.')">
    <x-pwa-head />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
    <title>@yield('title', 'Havenedge Tourlink | One Platform. Endless Journeys.')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:p-3" href="#main-content">Skip to content</a>
    <x-site-header />
    <main id="main-content">
        @yield('content')
    </main>
    <x-site-footer />
    @unless (request()->routeIs('terms'))
        <x-mobile-bottom-nav />
    @else
        <x-public-support-launcher />
    @endunless
</body>
</html>
