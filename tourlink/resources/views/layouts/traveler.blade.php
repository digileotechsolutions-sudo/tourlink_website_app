<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="view-transition" content="same-origin">
    <x-pwa-head />
    <title>@yield('title', 'Traveler | Havenedge Tourlink')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:p-3" href="#main-content">Skip to content</a>
    <main id="main-content">
        @if (session('status'))
            <div class="mx-4 mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950 sm:mx-8" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mx-4 mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-950 sm:mx-8" role="alert">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    <x-mobile-bottom-nav />
    <x-google-translate-runtime />
</body>
</html>
