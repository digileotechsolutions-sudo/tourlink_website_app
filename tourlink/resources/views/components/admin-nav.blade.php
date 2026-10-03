@props(['variant' => 'sidebar'])

@php
    $unreadSupportCount = \Illuminate\Support\Facades\Schema::hasTable('support_messages')
        ? \App\Models\SupportMessage::query()
            ->where('sender_type', \App\Models\SupportMessage::SENDER_CUSTOMER)
            ->whereNull('read_at')
            ->count()
        : 0;
    $items = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'active' => 'admin.users.*'],
        ['label' => 'Bookings', 'route' => 'admin.bookings.index', 'active' => 'admin.bookings.*'],
        ['label' => 'Trips', 'route' => 'admin.trips.index', 'active' => 'admin.trips.*'],
        ['label' => 'Payments', 'route' => 'admin.payments.index', 'active' => 'admin.payments.*'],
        ['label' => 'Inventory', 'route' => 'admin.vehicles.index', 'active' => 'admin.vehicles.*'],
        ['label' => 'Events', 'route' => 'admin.events.index', 'active' => 'admin.events.*'],
        ['label' => 'Blog', 'route' => 'admin.blog.index', 'active' => 'admin.blog.*'],
        ['label' => 'Reviews', 'route' => 'admin.reviews.index', 'active' => 'admin.reviews.*'],
        ['label' => 'Referrals', 'route' => 'admin.referrals.index', 'active' => 'admin.referrals.*'],
        ['label' => 'Reports', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*'],
        ['label' => 'Messages', 'route' => 'admin.messages.index', 'active' => 'admin.messages.*'],
        ['label' => 'Support', 'route' => 'admin.support.index', 'active' => 'admin.support.*'],
        ['label' => 'Settings', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*'],
    ];
    $supportItems = [
        ['label' => 'Verification', 'route' => 'admin.verification.index', 'active' => 'admin.verification.*'],
        ['label' => 'Catalog', 'route' => 'admin.catalog.index', 'active' => 'admin.catalog.*'],
        ['label' => 'Audit log', 'route' => 'admin.audit.index', 'active' => 'admin.audit.*'],
    ];
    $itemClass = $variant === 'mobile'
        ? 'block rounded px-3 py-2.5 text-sm font-semibold'
        : 'block rounded-r px-3 py-2.5 text-sm font-semibold';
@endphp

<nav aria-label="Admin navigation" class="grid gap-1">
    @foreach ($items as $item)
        @php($active = request()->routeIs($item['active']))
        @if (Route::has($item['route']))
            <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif class="{{ $itemClass }} {{ $active ? 'border-l-2 border-sun bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">{{ $item['label'] }}@if($item['label'] === 'Support' && $unreadSupportCount > 0)<span class="ml-2 rounded-full bg-pink-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ min(99, $unreadSupportCount) }}</span>@endif</a>
        @else
            <span aria-disabled="true" title="This admin section is not available yet" class="{{ $itemClass }} cursor-not-allowed text-white/35">{{ $item['label'] }}</span>
        @endif
    @endforeach

    <div class="my-2 border-t border-white/10"></div>
    @foreach ($supportItems as $item)
        @php($active = request()->routeIs($item['active']))
        @if (Route::has($item['route']))
            <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif class="{{ $itemClass }} {{ $active ? 'border-l-2 border-sun bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">{{ $item['label'] }}</a>
        @endif
    @endforeach
</nav>
