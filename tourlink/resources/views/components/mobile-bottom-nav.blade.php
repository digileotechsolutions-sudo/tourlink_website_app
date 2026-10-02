@auth
    @php
        $role = auth()->user()->role;
        $pageTitle = str($__env->yieldContent('title', 'TourLink'))->before('|')->trim();
        $dashboardRoute = match ($role) {
            \App\Role::Operator => 'operator.dashboard',
            \App\Role::VehicleOwner => 'vehicle-owner.dashboard',
            \App\Role::Traveler => 'dashboard',
            default => 'admin.dashboard',
        };
        $profileRoute = match ($role) {
            \App\Role::Operator => 'operator.profile',
            \App\Role::VehicleOwner => 'vehicle-owner.profile',
            \App\Role::Traveler => 'traveler.profile',
            default => 'admin.users.index',
        };
        $notificationsRoute = match ($role) {
            \App\Role::Operator => 'operator.section',
            \App\Role::VehicleOwner => 'vehicle-owner.section',
            \App\Role::Traveler => 'traveler.notifications',
            default => 'admin.verification.index',
        };
        $notificationsParameters = in_array($role, [\App\Role::Operator, \App\Role::VehicleOwner], true) ? ['section' => 'messages'] : [];
        $menuLinks = match ($role) {
            \App\Role::Operator => [['operator.dashboard', 'Dashboard'], ['operator.trips.index', 'Trips'], ['operator.trips.create', 'Create trip'], ['operator.bookings.index', 'Bookings'], ['operator.section', 'Customers', ['section' => 'customers']], ['operator.section', 'Vehicles', ['section' => 'vehicles']], ['operator.section', 'Messages', ['section' => 'messages']], ['operator.section', 'Reviews', ['section' => 'reviews']], ['operator.section', 'Earnings', ['section' => 'earnings']], ['operator.section', 'Payments', ['section' => 'payments']], ['operator.section', 'Verification', ['section' => 'verification']], ['operator.section', 'Analytics', ['section' => 'analytics']], ['referrals.index', 'Referrals'], ['operator.profile', 'Profile'], ['operator.section', 'Settings', ['section' => 'settings']]],
            \App\Role::VehicleOwner => [['vehicle-owner.dashboard', 'Dashboard'], ['vehicle-owner.vehicles.index', 'Vehicles'], ['vehicle-owner.vehicles.create', 'Add vehicle'], ['vehicle-owner.bookings.index', 'Bookings'], ['vehicle-owner.section', 'Earnings', ['section' => 'earnings']], ['vehicle-owner.section', 'Ratings', ['section' => 'ratings']], ['vehicle-owner.section', 'Messages', ['section' => 'messages']], ['vehicle-owner.section', 'Verification', ['section' => 'verification']], ['referrals.index', 'Referrals'], ['vehicle-owner.section', 'Settings', ['section' => 'settings']], ['vehicle-owner.profile', 'Profile']],
            \App\Role::Traveler => [['dashboard', 'Dashboard'], ['traveler.bookings', 'My bookings'], ['traveler.section', 'Upcoming trips', ['section' => 'upcoming']], ['traveler.section', 'Past trips', ['section' => 'past']], ['traveler.favorites', 'Favorites'], ['traveler.section', 'Payments', ['section' => 'payments']], ['traveler.section', 'Receipts', ['section' => 'receipts']], ['traveler.section', 'Messages', ['section' => 'messages']], ['traveler.notifications', 'Notifications'], ['traveler.section', 'Reviews', ['section' => 'reviews']], ['referrals.index', 'Referrals'], ['traveler.profile', 'Profile'], ['traveler.section', 'Settings', ['section' => 'settings']]],
            default => [['admin.dashboard', 'Dashboard'], ['admin.users.index', 'Users'], ['admin.bookings.index', 'Bookings'], ['admin.trips.index', 'Trips'], ['admin.payments.index', 'Payments'], ['admin.vehicles.index', 'Inventory'], ['admin.events.index', 'Events'], ['admin.blog.index', 'Blog'], ['admin.reviews.index', 'Reviews'], ['admin.referrals.index', 'Referrals'], ['admin.reports.index', 'Reports'], ['admin.messages.index', 'Messages'], ['admin.settings.index', 'Settings'], ['admin.verification.index', 'Verification'], ['admin.catalog.index', 'Catalog'], ['admin.audit.index', 'Audit log']],
        };
        $links = match ($role) {
            \App\Role::Operator => [['operator.dashboard', 'Dashboard'], ['operator.trips.index', 'Trips'], ['operator.bookings.index', 'Bookings'], ['operator.section', 'Messages', ['section' => 'messages']], ['operator.profile', 'Profile']],
            \App\Role::VehicleOwner => [['vehicle-owner.dashboard', 'Dashboard'], ['vehicle-owner.vehicles.index', 'Vehicles'], ['vehicle-owner.bookings.index', 'Bookings'], ['vehicle-owner.section', 'Messages', ['section' => 'messages']], ['vehicle-owner.profile', 'Profile']],
            \App\Role::Traveler => [['dashboard', 'Dashboard'], ['trips.index', 'Trips'], ['traveler.bookings', 'Bookings'], ['traveler.section', 'Chat', ['section' => 'messages']], ['traveler.profile', 'Profile']],
            default => [['admin.dashboard', 'Dashboard'], ['admin.trips.index', 'Trips'], ['admin.bookings.index', 'Bookings'], ['admin.messages.index', 'Chat'], ['admin.users.index', 'Profile']],
        };
    @endphp

    <header class="mobile-app-header lg:hidden" aria-label="App navigation">
        <a class="mobile-app-brand" href="{{ route($dashboardRoute) }}" aria-label="TourLink dashboard">
            <span class="mobile-app-brand__mark">TL</span>
        </a>
        <h1 class="mobile-app-title">{{ $pageTitle }}</h1>
        <div class="mobile-app-actions">
            @if ($role === \App\Role::Admin)
                <a class="mobile-app-icon" href="{{ route('admin.users.index') }}" aria-label="Search users">
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                </a>
            @endif
            <a class="mobile-app-icon" href="{{ route($notificationsRoute, $notificationsParameters) }}" aria-label="Notifications">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
            </a>
            <a class="mobile-app-icon" href="{{ route($profileRoute) }}" aria-label="Profile">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            </a>
            <details class="mobile-app-menu">
                <summary class="mobile-app-icon" aria-label="Open app menu"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></summary>
                <div class="mobile-menu-panel">
                    @foreach ($menuLinks as $menuLink)
                        @if (Route::has($menuLink[0]))<a href="{{ route($menuLink[0], $menuLink[2] ?? []) }}">{{ $menuLink[1] }}</a>@endif
                    @endforeach
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-200 pt-2">
                        @csrf
                        <button type="submit" class="w-full px-4 py-3 text-left font-bold text-red-700">Log out</button>
                    </form>
                </div>
            </details>
        </div>
        @if ($role === \App\Role::Admin)
            <form class="mobile-admin-search" method="GET" action="{{ route('admin.users.index') }}" role="search">
                <label class="sr-only" for="mobile-admin-search">Search admin users</label>
                <input id="mobile-admin-search" name="search" type="search" inputmode="search" value="{{ request()->routeIs('admin.users.*') ? request('search') : '' }}" placeholder="Search users...">
                <button type="submit" aria-label="Search users"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg></button>
            </form>
        @endif
    </header>

    <nav class="mobile-bottom-nav lg:hidden" aria-label="Main navigation">
        @foreach ($links as $link)
            @php($icon = match ($link[1]) { 'Dashboard' => 'dashboard', 'Trips' => 'trips', 'Bookings' => 'bookings', 'Messages', 'Chat' => 'messages', 'Profile' => 'profile', default => 'dashboard' })
            <a href="{{ route($link[0], $link[2] ?? []) }}" class="mobile-bottom-nav__item {{ request()->routeIs($link[0]) ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    @if ($icon === 'dashboard')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                    @elseif ($icon === 'trips')<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>
                    @elseif ($icon === 'bookings')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18m-13 4h3m-3 3h7"/>
                    @elseif ($icon === 'messages')<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/>
                    @else<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
                    @endif
                </svg>
                <span>{{ $link[1] }}</span>
            </a>
        @endforeach
    </nav>
@endauth
<x-pwa-status />
