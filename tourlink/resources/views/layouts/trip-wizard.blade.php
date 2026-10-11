<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('localization.languages.'.app()->getLocale().'.direction', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="view-transition" content="same-origin">
    <x-pwa-head />
    <title>@yield('title', 'Create a trip | Havenedge Tourlink')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900" @auth data-idle-logout data-idle-timeout-ms="{{ config('session.lifetime') * 60 * 1000 }}" data-idle-user="{{ auth()->id() }}" data-logout-url="{{ route('logout') }}" data-idle-login-url="{{ route('login') }}" data-idle-csrf="{{ csrf_token() }}" @endauth>
    <main class="min-h-screen px-3 py-3.5 sm:px-4 sm:py-4">
        @if(session('status'))
            <div class="mx-auto mb-3 max-w-[614px] rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900" role="status">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mx-auto mb-3 max-w-[614px] rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
