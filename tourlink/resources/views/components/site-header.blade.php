@php
    $dashboardRoute = match (auth()->user()?->role) {
        \App\Role::Admin => 'admin.dashboard',
        \App\Role::Operator => 'operator.dashboard',
        \App\Role::VehicleOwner => 'vehicle-owner.dashboard',
        \App\Role::Traveler => 'dashboard',
        default => 'home',
    };
@endphp

<header class="site-header {{ request()->routeIs('home') ? 'site-header--floating' : '' }}">
    <div class="site-header__inner container-page">
        @if (request()->routeIs('home'))
            <div class="site-header__mobile-app-shell" aria-label="{{ __('Tourlink app header') }}">
                <div class="site-header__mobile-app-bar">
                    <details class="site-header__mobile site-header__mobile-app-navigation">
                        <summary class="site-header__mobile-app-menu" aria-label="{{ __('Open navigation menu') }}">
                        <svg class="site-header__menu-open" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg class="site-header__menu-close" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="m6 6 12 12M18 6 6 18"></path>
                        </svg>
                        <span class="sr-only">{{ __('Toggle navigation menu') }}</span>
                        </summary>
                        @include('components.site-header-mobile-panel', ['dashboardRoute' => $dashboardRoute])
                    </details>

                    <a class="site-header__mobile-brand" href="{{ route('home') }}" aria-label="{{ __('Havenedge Tourlink home') }}">
                        <span class="site-header__mobile-brand-mark" aria-hidden="true">HT</span>
                        <span class="site-header__mobile-brand-copy">
                            <span class="site-header__mobile-brand-name">Tourlink</span>
                            <span class="site-header__mobile-brand-subtitle">{{ __('Travel made simple') }}</span>
                        </span>
                    </a>

                    <div class="site-header__mobile-app-actions">
                        <x-language-switcher id="mobile-site-language" />
                        <a class="site-header__mobile-app-action" href="{{ auth()->check() ? route($dashboardRoute) : route('login') }}" aria-label="{{ __('Profile') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M4 21a8 8 0 0 1 16 0"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <a class="site-header__mobile-search" href="{{ route('trips.index') }}" aria-label="{{ __('Search destinations') }}">
                    <span class="site-header__mobile-search-label">{{ __('Where do you want to go?') }}</span>
                    <span class="site-header__mobile-search-button" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M5 12h14"></path>
                            <path d="m13 5 7 7-7 7"></path>
                        </svg>
                    </span>
                </a>
            </div>
        @endif
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
                @include('components.site-header-mobile-panel', ['dashboardRoute' => $dashboardRoute])
            </details>
    </div>
</header>
