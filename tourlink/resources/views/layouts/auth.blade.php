<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="view-transition" content="same-origin">
    <x-pwa-head />
    <title>@yield('title', 'Sign in | TourLink')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="login-page">
        @yield('content')
    </main>
    <x-pwa-status />
</body>
</html>
