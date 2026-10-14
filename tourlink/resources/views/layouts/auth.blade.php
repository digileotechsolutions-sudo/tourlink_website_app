<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('localization.languages.'.app()->getLocale().'.direction', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="view-transition" content="same-origin">
    <x-pwa-head />
    <title>@yield('title', 'Sign in | Havenedge Tourlink')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page auth-page" @auth data-idle-logout data-idle-timeout-ms="{{ config('session.lifetime') * 60 * 1000 }}" data-idle-user="{{ auth()->id() }}" data-logout-url="{{ route('logout') }}" data-idle-login-url="{{ route('login') }}" data-idle-csrf="{{ csrf_token() }}" @endauth>
    <header class="auth-topbar site-header--floating">
        <a class="auth-brand site-header__brand" href="{{ route('home') }}" aria-label="{{ __('Havenedge Tourlink home') }}">
            <span class="site-header__brand-icon" aria-hidden="true"><span>HT</span></span>
            <span class="site-header__brand-copy">
                <span class="site-header__brand-name">Havenedge <strong>Tourlink</strong></span>
                <span class="site-header__tagline">{{ __('Trips · Vehicles · Together') }}</span>
            </span>
        </a>
        <div class="auth-language-switcher"><x-language-switcher /></div>
    </header>
    <main class="login-page">
        @yield('content')
    </main>
    <x-portal-footer />
    <x-pwa-status />
    <x-public-support-launcher />
</body>
</html>
