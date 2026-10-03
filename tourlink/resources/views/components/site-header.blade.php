@php
    $dashboardRoute = match (auth()->user()?->role) {
        \App\Role::Admin => 'admin.dashboard',
        \App\Role::Operator => 'operator.dashboard',
        \App\Role::VehicleOwner => 'vehicle-owner.dashboard',
        \App\Role::Traveler => 'dashboard',
        default => 'home',
    };
@endphp

<header class="site-header">
    <div class="site-header__inner container-page">
        <a class="site-header__brand" href="{{ route('home') }}" aria-label="{{ __('Havenedge Tourlink home') }}">
            <span class="site-header__brand-icon" aria-hidden="true">
                <span>HT</span>
            </span>
            <span class="site-header__brand-copy">
                <span class="site-header__brand-name">Havenedge <strong>Tourlink</strong></span>
                <span class="site-header__tagline">{{ __('Trips · Vehicles · Together') }}</span>
            </span>
        </a>

        <nav class="site-header__nav" aria-label="{{ __('Main navigation') }}">
            <a href="{{ route('trips.index') }}" @class(['is-active' => request()->routeIs('trips.*')])>{{ __('Find a trip') }}</a>
            <a href="{{ route('vehicles.index') }}" @class(['is-active' => request()->routeIs('vehicles.*')])>{{ __('Hire a vehicle') }}</a>
            <a href="{{ route('blog.index') }}" @class(['is-active' => request()->routeIs('blog.*')])>{{ __('Journal') }}</a>
            <a href="{{ route('about') }}" @class(['is-active' => request()->routeIs('about')])>{{ __('About') }}</a>
            <a href="{{ route('home') }}#destinations">{{ __('Destinations') }}</a>
        </nav>

        <div class="site-header__actions">
            <x-language-switcher />
            <div class="site-header__account-actions">
                @auth
                    @if (Route::has($dashboardRoute))
                        <a class="site-header__account-link" href="{{ route($dashboardRoute) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M4 21a8 8 0 0 1 16 0"></path>
                            </svg>
                            <span>{{ __('My account') }}</span>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="site-header__join" type="submit">{{ __('Log out') }}</button>
                    </form>
                @else
                    <a class="site-header__login" href="{{ route('login') }}">{{ __('Log in') }}</a>
                    <a class="site-header__join" href="{{ route('register') }}">{{ __('Join Tourlink') }}</a>
                @endauth
            </div>
        </div>

        <details class="site-header__mobile">
            <summary aria-label="{{ __('Open navigation menu') }}">
                <svg class="site-header__menu-open" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                <svg class="site-header__menu-close" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="m6 6 12 12M18 6 6 18"></path>
                </svg>
                <span class="sr-only">{{ __('Toggle navigation menu') }}</span>
            </summary>
            <div class="site-header__mobile-panel">
                <p class="site-header__mobile-eyebrow">{{ __('Explore Havenedge Tourlink') }}</p>
                <nav aria-label="{{ __('Mobile navigation') }}">
                    <a href="{{ route('trips.index') }}">{{ __('Find a trip') }} <span aria-hidden="true">→</span></a>
                    <a href="{{ route('vehicles.index') }}">{{ __('Hire a vehicle') }} <span aria-hidden="true">→</span></a>
                    <a href="{{ route('blog.index') }}">{{ __('Journal') }} <span aria-hidden="true">→</span></a>
                    <a href="{{ route('about') }}">{{ __('About us') }} <span aria-hidden="true">→</span></a>
                    <a href="{{ route('contact') }}">{{ __('Talk to us') }} <span aria-hidden="true">→</span></a>
                    <a href="{{ route('home') }}#destinations">{{ __('Destinations') }} <span aria-hidden="true">→</span></a>
                </nav>
                <div class="site-header__mobile-account">
                    @auth
                        @if (Route::has($dashboardRoute))
                            <a class="site-header__mobile-primary" href="{{ route($dashboardRoute) }}">{{ __('My account') }}</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">{{ __('Log out') }}</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">{{ __('Log in') }}</a>
                        <a class="site-header__mobile-primary" href="{{ route('register') }}">{{ __('Join Tourlink') }}</a>
                    @endauth
                </div>
            </div>
        </details>
    </div>
</header>
