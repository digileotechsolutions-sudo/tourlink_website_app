@php
    $user = auth()->user();
    $role = $user->role;
    $pageTitle = str($__env->yieldContent('title', 'Havenedge Tourlink'))->before('|')->trim();
    $dashboardRoute = match ($role) {
        \App\Role::Operator => 'operator.dashboard',
        \App\Role::VehicleOwner => 'vehicle-owner.dashboard',
        \App\Role::Traveler => 'dashboard',
        default => 'admin.dashboard',
    };
    $displayRole = match ($role) {
        \App\Role::Operator => 'Tour operator',
        \App\Role::VehicleOwner => 'Vehicle owner',
        \App\Role::Traveler => 'Traveler',
        default => 'Administrator',
    };
    $supportUnreadCount = in_array($role, [\App\Role::Traveler, \App\Role::Admin], true)
        && \Illuminate\Support\Facades\Schema::hasTable('support_messages')
        && \Illuminate\Support\Facades\Schema::hasTable('support_conversations')
        ? \App\Models\SupportMessage::query()
            ->where('sender_type', $role === \App\Role::Admin ? 'customer' : 'admin')
            ->whereNull('read_at')
            ->whereHas('conversation', fn ($query) => $role === \App\Role::Admin
                ? $query
                : $query->where('user_id', $user->id))
            ->count()
        : 0;
    $profileRoute = match ($role) {
        \App\Role::Operator => 'operator.profile',
        \App\Role::VehicleOwner => 'vehicle-owner.profile',
        \App\Role::Traveler => 'traveler.profile',
        default => null,
    };
    $profileIncomplete = match ($role) {
        \App\Role::Operator => blank($user->operatorProfile?->company_name) || blank($user->operatorProfile?->description),
        \App\Role::VehicleOwner => blank($user->vehicleOwnerProfile?->business_name) || blank($user->vehicleOwnerProfile?->description),
        \App\Role::Traveler => blank($user->travelerProfile?->bio),
        default => false,
    };
    $profileReminderMessage = match ($role) {
        \App\Role::Operator => 'Add your company name and a short business description.',
        \App\Role::VehicleOwner => 'Add your business name and a short description.',
        \App\Role::Traveler => 'Add a short bio so providers can get to know you.',
        default => '',
    };
    $settingsRoute = match ($role) {
        \App\Role::Operator => ['operator.section', ['section' => 'settings']],
        \App\Role::VehicleOwner => ['vehicle-owner.section', ['section' => 'settings']],
        \App\Role::Traveler => ['traveler.section', ['section' => 'settings']],
        default => ['admin.settings.index', []],
    };
    $notifications = match ($role) {
        \App\Role::Operator => ['operator.section', ['section' => 'messages'], 'Messages'],
        \App\Role::VehicleOwner => ['vehicle-owner.section', ['section' => 'messages'], 'Messages'],
        \App\Role::Traveler => ['traveler.notifications', [], 'Notifications'],
        default => ['admin.verification.index', [], 'Review queue'],
    };
    $groups = match ($role) {
        \App\Role::Operator => [
            ['label' => 'Workspace', 'items' => [
                ['label' => 'Overview', 'route' => 'operator.dashboard', 'icon' => 'dashboard'],
                ['label' => 'Trips', 'route' => 'operator.trips.index', 'active' => 'operator.trips.*', 'except' => 'operator.trips.create', 'icon' => 'route'],
                ['label' => 'Create trip', 'route' => 'operator.trips.create', 'icon' => 'plus'],
                ['label' => 'Bookings', 'route' => 'operator.bookings.index', 'active' => 'operator.bookings.*', 'icon' => 'calendar'],
                ['label' => 'Customers', 'route' => 'operator.section', 'section' => 'customers', 'parameters' => ['section' => 'customers'], 'icon' => 'users'],
                ['label' => 'Vehicles', 'route' => 'operator.section', 'section' => 'vehicles', 'parameters' => ['section' => 'vehicles'], 'icon' => 'vehicle'],
            ]],
            ['label' => 'Performance', 'items' => [
                ['label' => 'Messages', 'route' => 'operator.section', 'section' => 'messages', 'parameters' => ['section' => 'messages'], 'icon' => 'message'],
                ['label' => 'Reviews', 'route' => 'operator.section', 'section' => 'reviews', 'parameters' => ['section' => 'reviews'], 'icon' => 'star'],
                ['label' => 'Earnings', 'route' => 'operator.section', 'section' => 'earnings', 'parameters' => ['section' => 'earnings'], 'icon' => 'wallet'],
                ['label' => 'Payments', 'route' => 'operator.section', 'section' => 'payments', 'parameters' => ['section' => 'payments'], 'icon' => 'receipt'],
                ['label' => 'Analytics', 'route' => 'operator.section', 'section' => 'analytics', 'parameters' => ['section' => 'analytics'], 'icon' => 'chart'],
                ['label' => 'Referrals', 'route' => 'referrals.index', 'active' => 'referrals.*', 'icon' => 'gift'],
            ]],
            ['label' => 'Account', 'items' => [
                ['label' => 'Verification', 'route' => 'operator.section', 'section' => 'verification', 'parameters' => ['section' => 'verification'], 'icon' => 'shield'],
                ['label' => 'Settings', 'route' => 'operator.section', 'section' => 'settings', 'parameters' => ['section' => 'settings'], 'icon' => 'settings'],
                ['label' => 'Profile', 'route' => 'operator.profile', 'icon' => 'user'],
            ]],
        ],
        \App\Role::VehicleOwner => [
            ['label' => 'Workspace', 'items' => [
                ['label' => 'Overview', 'route' => 'vehicle-owner.dashboard', 'icon' => 'dashboard'],
                ['label' => 'Vehicles', 'route' => 'vehicle-owner.vehicles.index', 'active' => 'vehicle-owner.vehicles.*', 'except' => 'vehicle-owner.vehicles.create', 'icon' => 'vehicle'],
                ['label' => 'Add vehicle', 'route' => 'vehicle-owner.vehicles.create', 'icon' => 'plus'],
                ['label' => 'Bookings', 'route' => 'vehicle-owner.bookings.index', 'active' => 'vehicle-owner.bookings.*', 'icon' => 'calendar'],
            ]],
            ['label' => 'Performance', 'items' => [
                ['label' => 'Earnings', 'route' => 'vehicle-owner.section', 'section' => 'earnings', 'parameters' => ['section' => 'earnings'], 'icon' => 'wallet'],
                ['label' => 'Ratings', 'route' => 'vehicle-owner.section', 'section' => 'ratings', 'parameters' => ['section' => 'ratings'], 'icon' => 'star'],
                ['label' => 'Messages', 'route' => 'vehicle-owner.section', 'section' => 'messages', 'parameters' => ['section' => 'messages'], 'icon' => 'message'],
                ['label' => 'Referrals', 'route' => 'referrals.index', 'active' => 'referrals.*', 'icon' => 'gift'],
            ]],
            ['label' => 'Account', 'items' => [
                ['label' => 'Verification', 'route' => 'vehicle-owner.section', 'section' => 'verification', 'parameters' => ['section' => 'verification'], 'icon' => 'shield'],
                ['label' => 'Settings', 'route' => 'vehicle-owner.section', 'section' => 'settings', 'parameters' => ['section' => 'settings'], 'icon' => 'settings'],
                ['label' => 'Profile', 'route' => 'vehicle-owner.profile', 'icon' => 'user'],
            ]],
        ],
        \App\Role::Traveler => [
            ['label' => 'Travel', 'items' => [
                ['label' => 'Overview', 'route' => 'dashboard', 'icon' => 'dashboard'],
                ['label' => 'My bookings', 'route' => 'traveler.bookings', 'active' => 'traveler.bookings*', 'icon' => 'calendar'],
                ['label' => 'Upcoming trips', 'route' => 'traveler.section', 'section' => 'upcoming', 'parameters' => ['section' => 'upcoming'], 'icon' => 'route'],
                ['label' => 'Past trips', 'route' => 'traveler.section', 'section' => 'past', 'parameters' => ['section' => 'past'], 'icon' => 'history'],
                ['label' => 'Favorites', 'route' => 'traveler.favorites', 'active' => 'traveler.favorites*', 'icon' => 'star'],
            ]],
            ['label' => 'Account', 'items' => [
                ['label' => 'Payments', 'route' => 'traveler.section', 'section' => 'payments', 'parameters' => ['section' => 'payments'], 'icon' => 'wallet'],
                ['label' => 'Receipts', 'route' => 'traveler.section', 'section' => 'receipts', 'parameters' => ['section' => 'receipts'], 'icon' => 'receipt'],
                ['label' => 'Messages', 'route' => 'traveler.section', 'section' => 'messages', 'parameters' => ['section' => 'messages'], 'icon' => 'message'],
                ['label' => 'Support', 'route' => 'support.index', 'active' => 'support.*', 'icon' => 'message'],
                ['label' => 'Notifications', 'route' => 'traveler.notifications', 'active' => 'traveler.notifications*', 'icon' => 'bell'],
                ['label' => 'Reviews', 'route' => 'traveler.section', 'section' => 'reviews', 'parameters' => ['section' => 'reviews'], 'icon' => 'star'],
                ['label' => 'Referrals', 'route' => 'referrals.index', 'active' => 'referrals.*', 'icon' => 'gift'],
                ['label' => 'Profile', 'route' => 'traveler.profile', 'icon' => 'user'],
                ['label' => 'Settings', 'route' => 'traveler.section', 'section' => 'settings', 'parameters' => ['section' => 'settings'], 'icon' => 'settings'],
            ]],
        ],
        default => [
            ['label' => 'Overview', 'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'dashboard'],
                ['label' => 'Reports', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'icon' => 'chart'],
            ]],
            ['label' => 'Marketplace', 'items' => [
                ['label' => 'Trips', 'route' => 'admin.trips.index', 'active' => 'admin.trips.*', 'icon' => 'route'],
                ['label' => 'Vehicles', 'route' => 'admin.vehicles.index', 'active' => 'admin.vehicles.*', 'icon' => 'vehicle'],
                ['label' => 'Catalog', 'route' => 'admin.catalog.index', 'active' => 'admin.catalog.*', 'icon' => 'database'],
                ['label' => 'Events', 'route' => 'admin.events.index', 'active' => 'admin.events.*', 'icon' => 'calendar'],
                ['label' => 'Blog', 'route' => 'admin.blog.index', 'active' => 'admin.blog.*', 'icon' => 'document'],
            ]],
            ['label' => 'Operations', 'items' => [
                ['label' => 'Users', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'users'],
                ['label' => 'Bookings', 'route' => 'admin.bookings.index', 'active' => 'admin.bookings.*', 'icon' => 'calendar'],
                ['label' => 'Payments', 'route' => 'admin.payments.index', 'active' => 'admin.payments.*', 'icon' => 'wallet'],
                ['label' => 'Refunds', 'route' => 'admin.refunds.index', 'active' => 'admin.refunds.*', 'icon' => 'receipt'],
                ['label' => 'Commissions', 'route' => 'admin.commissions.index', 'active' => 'admin.commissions.*', 'icon' => 'chart'],
                ['label' => 'Payouts', 'route' => 'admin.payouts.index', 'active' => 'admin.payouts.*', 'icon' => 'wallet'],
                ['label' => 'Verification', 'route' => 'admin.verification.index', 'active' => 'admin.verification.*', 'icon' => 'shield'],
                ['label' => 'Reviews', 'route' => 'admin.reviews.index', 'active' => 'admin.reviews.*', 'icon' => 'star'],
                ['label' => 'Messages', 'route' => 'admin.messages.index', 'active' => 'admin.messages.*', 'icon' => 'message'],
                ['label' => 'Support', 'route' => 'admin.support.index', 'active' => 'admin.support.*', 'icon' => 'message'],
                ['label' => 'Referrals', 'route' => 'admin.referrals.index', 'active' => 'admin.referrals.*', 'icon' => 'gift'],
            ]],
            ['label' => 'System', 'items' => [
                ['label' => 'Settings', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'icon' => 'settings'],
                ['label' => 'Audit log', 'route' => 'admin.audit.index', 'active' => 'admin.audit.*', 'icon' => 'clipboard'],
            ]],
        ],
    };
    $bottomLinks = match ($role) {
        \App\Role::Operator => [['Home', 'operator.dashboard', 'dashboard'], ['Trips', 'operator.trips.index', 'route'], ['Bookings', 'operator.bookings.index', 'calendar'], ['Messages', 'operator.section', 'message', ['section' => 'messages']], ['Profile', 'operator.profile', 'user']],
        \App\Role::VehicleOwner => [['Home', 'vehicle-owner.dashboard', 'dashboard'], ['Vehicles', 'vehicle-owner.vehicles.index', 'vehicle'], ['Bookings', 'vehicle-owner.bookings.index', 'calendar'], ['Messages', 'vehicle-owner.section', 'message', ['section' => 'messages']], ['Profile', 'vehicle-owner.profile', 'user']],
        \App\Role::Traveler => [['Home', 'dashboard', 'dashboard'], ['Trips', 'trips.index', 'route'], ['Bookings', 'traveler.bookings', 'calendar'], ['Messages', 'traveler.section', 'message', ['section' => 'messages']], ['Profile', 'traveler.profile', 'user']],
        default => [['Home', 'admin.dashboard', 'dashboard'], ['Trips', 'admin.trips.index', 'route'], ['Bookings', 'admin.bookings.index', 'calendar'], ['Messages', 'admin.messages.index', 'message'], ['Settings', 'admin.settings.index', 'settings']],
    };
    $pendingVerifications = $role === \App\Role::Admin && isset($statistics)
        ? (int) ($statistics['pendingVerification'] ?? 0)
        : 0;
@endphp

<div class="dashboard-chrome">
    <aside class="portal-sidebar" data-portal-sidebar aria-label="Application sidebar">
        <a href="{{ route($dashboardRoute) }}" class="portal-brand" aria-label="Havenedge Tourlink {{ $displayRole }} dashboard"><span class="portal-brand__mark" aria-hidden="true">HT</span><span class="portal-brand__name">Havenedge Tourlink</span></a>
        <nav class="portal-sidebar__nav" aria-label="Main navigation">@include('components.portal-navigation-links', ['groups' => $groups])</nav>
        <div class="portal-sidebar__bottom">
            <div class="portal-sidebar__identity"><span class="portal-avatar" aria-hidden="true">{{ str($user->name)->substr(0, 1)->upper() }}</span><span class="portal-sidebar__identity-copy"><strong>{{ $user->name }}</strong><small>{{ $displayRole }}</small></span></div>
            <button class="portal-collapse-button" type="button" data-sidebar-toggle aria-expanded="true" aria-label="Collapse sidebar" title="Collapse sidebar"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m15 18-6-6 6-6M4 4v16"/></svg><span class="portal-nav-label">Collapse sidebar</span></button>
        </div>
    </aside>

    <header class="portal-topbar {{ $role === \App\Role::Admin ? 'portal-topbar--search' : '' }}">
        <div class="portal-topbar__row">
            <button class="portal-icon-button portal-menu-button" type="button" data-drawer-open aria-controls="portal-drawer" aria-expanded="false" aria-label="Open navigation"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            <a href="{{ route($dashboardRoute) }}" class="portal-mobile-brand" aria-label="Havenedge Tourlink dashboard"><span class="portal-brand__mark" aria-hidden="true">HT</span></a>
            <h1 class="portal-page-title">{{ $pageTitle }}</h1>
            <div class="portal-topbar__actions">
                @if ($role === \App\Role::Admin)
                    <a class="portal-icon-button portal-settings-shortcut" href="{{ route('admin.settings.index') }}" aria-label="System settings" title="System settings"><span class="portal-icon" aria-hidden="true">@include('components.portal-icon', ['name' => 'settings'])</span></a>
                @endif
                <a class="portal-icon-button portal-notifications" href="{{ route($notifications[0], $notifications[1]) }}" aria-label="{{ $notifications[2] }}" title="{{ $notifications[2] }}"><span class="portal-icon" aria-hidden="true">@include('components.portal-icon', ['name' => 'bell'])</span>@if ($pendingVerifications > 0)<span class="portal-notification-count">{{ min(99, $pendingVerifications) }}</span>@endif</a>
                <details class="portal-profile-menu">
                    <summary class="portal-profile-menu__trigger" aria-label="Open profile menu"><span class="portal-avatar" aria-hidden="true">{{ str($user->name)->substr(0, 1)->upper() }}</span><span class="portal-profile-menu__name">{{ $user->name }}</span><svg class="portal-profile-menu__chevron" viewBox="0 0 20 20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 7 5 5 5-5"/></svg></summary>
                    <div class="portal-profile-menu__panel">
                        <div class="portal-profile-menu__identity"><strong>{{ $user->name }}</strong><span>{{ $displayRole }}</span></div>
                        @if ($profileRoute && Route::has($profileRoute))<a href="{{ route($profileRoute) }}">Profile</a>@endif
                        @if (Route::has($settingsRoute[0]))<a href="{{ route($settingsRoute[0], $settingsRoute[1]) }}">{{ $role === \App\Role::Admin ? 'System settings' : 'Settings' }}</a>@endif
                        <a href="{{ route('password.change') }}">Password and security</a>
                        <a href="{{ route('home') }}">View website</a>
                        <a href="{{ route('home') }}#how-it-works">Help</a>
                        <a href="{{ route('contact') }}">Support</a>
                        @if (Route::has('privacy'))<a href="{{ route('privacy') }}">Privacy policy</a>@endif
                        @if (Route::has('terms'))<a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms</a>@endif
                        <form method="POST" action="{{ route('logout') }}" class="portal-profile-menu__logout">@csrf<button type="submit">Log out</button></form>
                    </div>
                </details>
            </div>
        </div>
        @if ($role === \App\Role::Admin)
            <form class="portal-global-search" method="GET" action="{{ route('admin.users.index') }}" role="search">
                <label class="sr-only" for="portal-global-search">Search users</label><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                <input id="portal-global-search" name="search" type="search" inputmode="search" value="{{ request()->routeIs('admin.users.*') ? request('search') : '' }}" placeholder="Search users">
                <button type="submit">Search</button>
            </form>
        @endif
    </header>

    <dialog class="portal-drawer" id="portal-drawer" data-portal-drawer aria-label="Main navigation">
        <div class="portal-drawer__head"><a href="{{ route($dashboardRoute) }}" class="portal-brand" aria-label="Havenedge Tourlink {{ $displayRole }} dashboard"><span class="portal-brand__mark" aria-hidden="true">HT</span><span class="portal-brand__name">Havenedge Tourlink</span></a><form method="dialog"><button class="portal-icon-button" aria-label="Close navigation"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg></button></form></div>
        <nav class="portal-sidebar__nav" aria-label="Main navigation">@include('components.portal-navigation-links', ['groups' => $groups])</nav>
        <div class="portal-drawer__foot"><strong>{{ $user->name }}</strong><span>{{ $displayRole }}</span></div>
    </dialog>

    <nav class="portal-bottom-nav" aria-label="Frequently used modules">
        @foreach ($bottomLinks as $link)
            @if (Route::has($link[1]))
                @php($active = request()->routeIs($link[1]) && (! isset($link[3]) || request()->route('section') === ($link[3]['section'] ?? null)))
                <a href="{{ route($link[1], $link[3] ?? []) }}" class="portal-bottom-nav__item {{ $active ? 'is-active' : '' }}" @if ($active) aria-current="page" @endif title="{{ $link[0] }}"><span class="portal-icon" aria-hidden="true">@include('components.portal-icon', ['name' => $link[2]])</span><span>{{ $link[0] }}</span></a>
            @endif
        @endforeach
    </nav>

    @if ($profileIncomplete && $profileRoute && Route::has($profileRoute))
        <aside class="profile-reminder {{ $role === \App\Role::Admin ? 'profile-reminder--search' : '' }}" data-profile-reminder data-user-id="{{ $user->id }}" role="status" aria-labelledby="profile-reminder-title">
            <span class="profile-reminder__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0m-4-7 2 2 4-4"/></svg></span>
            <div class="profile-reminder__content">
                <strong id="profile-reminder-title">Complete your profile</strong>
                <p>{{ $profileReminderMessage }}</p>
                <a href="{{ route($profileRoute) }}">Go to profile <span aria-hidden="true">&rarr;</span></a>
            </div>
            <button class="profile-reminder__dismiss" type="button" data-profile-reminder-dismiss aria-label="Dismiss profile reminder" title="Dismiss">&times;</button>
        </aside>
    @endif
</div>