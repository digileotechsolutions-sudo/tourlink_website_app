<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'Discover trips, hire verified vehicles and travel Kenya with confidence.')">
    <meta name="theme-color" content="#063b00">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>@yield('title', 'TourLink | One Platform. Endless Journeys.')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:p-3" href="#main-content">Skip to content</a>
    <x-site-header />
    <main id="main-content">
        @yield('content')
    </main>
    <x-site-footer />
    <x-mobile-bottom-nav />
</body>
</html>
