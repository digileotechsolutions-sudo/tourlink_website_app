<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('localization.languages.'.app()->getLocale().'.direction', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="view-transition" content="same-origin">
    <x-pwa-head />
    <title>@yield('title', 'Sign in | Havenedge Tourlink')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page auth-page" @auth data-idle-logout data-idle-timeout-ms="{{ config('session.lifetime') * 60 * 1000 }}" data-idle-user="{{ auth()->id() }}" data-logout-url="{{ route('logout') }}" data-idle-login-url="{{ route('login') }}" data-idle-csrf="{{ csrf_token() }}" @endauth>
    <div class="auth-language-switcher"><x-language-switcher /></div>
    <main class="login-page">
        @yield('content')
    </main>
    <x-portal-footer />
    <x-pwa-status />
    <x-public-support-launcher />
</body>
</html>
