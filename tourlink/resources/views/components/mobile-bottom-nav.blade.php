@auth
<details class="mobile-menu-fab lg:hidden">
    <summary aria-label="Open dashboard menu"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg></summary>
    <div class="mobile-menu-panel">
        @php
            $menuLinks = match (auth()->user()->role) {
                \App\Role::Operator => [['operator.dashboard', 'Dashboard'], ['operator.trips.index', 'Trips'], ['operator.trips.create', 'Create trip'], ['operator.bookings.index', 'Bookings'], ['operator.section', 'Customers', ['section' => 'customers']], ['operator.section', 'Vehicles', ['section' => 'vehicles']], ['operator.section', 'Messages', ['section' => 'messages']], ['operator.section', 'Reviews', ['section' => 'reviews']], ['operator.section', 'Earnings', ['section' => 'earnings']], ['operator.section', 'Payments', ['section' => 'payments']], ['operator.section', 'Verification', ['section' => 'verification']], ['operator.section', 'Analytics', ['section' => 'analytics']], ['operator.profile', 'Profile'], ['operator.section', 'Settings', ['section' => 'settings']]],
                \App\Role::VehicleOwner => [['vehicle-owner.dashboard', 'Dashboard'], ['vehicle-owner.vehicles.index', 'Vehicles'], ['vehicle-owner.vehicles.create', 'Add vehicle'], ['vehicle-owner.bookings.index', 'Bookings'], ['vehicle-owner.section', 'Earnings', ['section' => 'earnings']], ['vehicle-owner.section', 'Ratings', ['section' => 'ratings']], ['vehicle-owner.section', 'Messages', ['section' => 'messages']], ['vehicle-owner.section', 'Verification', ['section' => 'verification']], ['vehicle-owner.section', 'Settings', ['section' => 'settings']], ['vehicle-owner.profile', 'Profile']],
                \App\Role::Traveler => [['dashboard', 'Dashboard'], ['traveler.bookings', 'My bookings'], ['traveler.section', 'Upcoming trips', ['section' => 'upcoming']], ['traveler.section', 'Past trips', ['section' => 'past']], ['traveler.favorites', 'Favorites'], ['traveler.section', 'Payments', ['section' => 'payments']], ['traveler.section', 'Receipts', ['section' => 'receipts']], ['traveler.section', 'Messages', ['section' => 'messages']], ['traveler.notifications', 'Notifications'], ['traveler.section', 'Reviews', ['section' => 'reviews']], ['traveler.profile', 'Profile'], ['traveler.section', 'Settings', ['section' => 'settings']]],
                default => [['admin.dashboard', 'Dashboard'], ['admin.users.index', 'Users'], ['admin.bookings.index', 'Bookings'], ['admin.trips.index', 'Trips'], ['admin.payments.index', 'Payments'], ['admin.vehicles.index', 'Inventory'], ['admin.events.index', 'Events'], ['admin.blog.index', 'Blog'], ['admin.reviews.index', 'Reviews'], ['admin.reports.index', 'Reports'], ['admin.messages.index', 'Messages'], ['admin.settings.index', 'Settings'], ['admin.verification.index', 'Verification'], ['admin.catalog.index', 'Catalog'], ['admin.audit.index', 'Audit log']],
            };
        @endphp
        @foreach($menuLinks as $menuLink)
            @if(Route::has($menuLink[0]))<a href="{{ route($menuLink[0], $menuLink[2] ?? []) }}">{{ $menuLink[1] }}</a>@endif
        @endforeach
        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-200 pt-2">
            @csrf
            <button type="submit" class="w-full px-4 py-3 text-left font-bold text-red-700">Log out</button>
        </form>
    </div>
</details>
<nav class="mobile-bottom-nav lg:hidden" aria-label="Mobile navigation">
    @php
        $role = auth()->user()->role;
        $links = match ($role) {
            \App\Role::Operator => [['operator.dashboard', 'Dashboard'], ['operator.trips.index', 'Trips'], ['operator.bookings.index', 'Bookings'], ['operator.section', 'Messages', ['section' => 'messages']], ['operator.profile', 'Profile']],
            \App\Role::VehicleOwner => [['vehicle-owner.dashboard', 'Dashboard'], ['vehicle-owner.vehicles.index', 'Vehicles'], ['vehicle-owner.bookings.index', 'Bookings'], ['vehicle-owner.section', 'Messages', ['section' => 'messages']], ['vehicle-owner.profile', 'Profile']],
            \App\Role::Traveler => [['dashboard', 'Dashboard'], ['trips.index', 'Trips'], ['traveler.bookings', 'Bookings'], ['traveler.section', 'Chat', ['section' => 'messages']], ['traveler.profile', 'Profile']],
            default => [['admin.dashboard', 'Dashboard'], ['admin.trips.index', 'Trips'], ['admin.bookings.index', 'Bookings'], ['admin.messages.index', 'Chat'], ['admin.users.index', 'Profile']],
        };
    @endphp
    @foreach($links as $link)
        <a href="{{ route($link[0], $link[2] ?? []) }}" class="mobile-bottom-nav__item {{ request()->routeIs($link[0]) ? 'is-active' : '' }}">{{ $link[1] }}</a>
    @endforeach
</nav>
@endauth
