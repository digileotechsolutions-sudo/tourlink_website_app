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