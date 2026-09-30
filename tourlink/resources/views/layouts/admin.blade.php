<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-pwa-head />
    <title>@yield('title', 'Admin | TourLink')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:p-3" href="#admin-main">Skip to content</a>
    <div class="flex min-h-screen">
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col bg-ink px-4 py-5 text-white lg:flex">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 pb-6" aria-label="TourLink admin dashboard">
                <span class="grid size-10 place-items-center rounded bg-sun text-sm font-black text-ink">TL</span>
                <span><span class="block text-lg font-extrabold">TourLink</span><span class="block text-[10px] font-semibold uppercase text-white/55">Administration</span></span>
            </a>
            <x-admin-nav />
            <div class="mt-auto border-t border-white/10 pt-4">
                <div class="mb-3 px-3"><p class="truncate text-sm font-bold">{{ auth()->user()->name }}</p><p class="text-xs text-white/55">Administrator</p></div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full rounded px-3 py-2.5 text-left text-sm font-semibold text-white/70 hover:bg-white/5 hover:text-white">Log out</button></form>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex min-h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('admin.dashboard') }}" class="grid size-9 shrink-0 place-items-center rounded bg-ink text-xs font-black text-white lg:hidden">TL</a>
                    <form method="GET" action="{{ route('admin.users.index') }}" class="min-w-0 flex-1">
                        <label class="sr-only" for="admin-global-search">Search users</label>
                        <input id="admin-global-search" name="search" value="{{ request()->routeIs('admin.users.*') ? request('search') : '' }}" placeholder="Search users..." class="min-h-10 w-full max-w-xl rounded border border-slate-200 bg-slate-50 px-3 text-sm placeholder:text-slate-500 focus:border-emerald-700 focus:bg-white">
                    </form>
                    <a href="{{ route('admin.verification.index') }}" class="relative inline-flex min-h-10 shrink-0 items-center gap-2 rounded px-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" aria-label="Notifications, verification requests">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="hidden sm:inline">Notifications</span>
                        @if (isset($statistics) && $statistics['pendingVerification'] > 0)<span class="grid size-5 place-items-center rounded-full bg-orange-100 text-[10px] font-bold text-orange-800">{{ min(99, $statistics['pendingVerification']) }}</span>@endif
                    </a>
                    <a href="{{ route('password.change') }}" class="inline-flex min-h-10 shrink-0 items-center rounded px-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" aria-label="Change password">Password</a>
                    <div class="flex shrink-0 items-center gap-2 border-l border-slate-200 pl-4">
                        <span class="grid size-9 place-items-center rounded-full bg-emerald-100 text-sm font-black text-emerald-900">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <span class="hidden max-w-36 truncate text-sm font-semibold text-slate-800 xl:block">{{ auth()->user()->name }}</span>
                        <a href="{{ route('home') }}" class="inline-flex min-h-10 items-center gap-2 rounded px-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" aria-label="View the public website">
                            <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 4h6v6M20 4l-8 8M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span class="hidden xl:inline">View website</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="min-h-10 rounded px-3 text-sm font-bold text-slate-600 hover:bg-slate-100">Log out</button></form>
                    </div>
                </div>
            </header>
            <main id="admin-main" class="min-w-0">
                @yield('content')
                <x-mobile-bottom-nav />
            </main>
        </div>
    </div>
</body>
</html>
