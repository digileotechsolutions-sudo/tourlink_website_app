@extends('layouts.admin')

@section('title', __('Admin review').' | Havenedge Tourlink')

@section('content')
    <div class="min-h-[calc(100vh-4rem)] bg-slate-50 px-4 py-6 sm:px-6 lg:px-8 lg:py-9">
        <div class="mx-auto max-w-[1600px]">
        <header class="flex flex-wrap items-end justify-between gap-5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-emerald-800">{{ __('Havenedge Tourlink administration') }}</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950">{{ __('Dashboard') }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ __('Welcome back, :name.', ['name' => auth()->user()->name]) }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs font-semibold text-slate-500">{{ now()->locale(app()->getLocale())->translatedFormat('l, F j, Y') }}</span>
                <a href="{{ route('admin.verification.index') }}" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-ink px-4 text-sm font-bold text-white shadow-sm hover:bg-emerald-950">{{ __('Review queue') }} <span class="grid size-5 place-items-center rounded-full bg-sun text-[10px] text-ink">{{ min(99, $statistics['pendingVerification']) }}</span></a>
            </div>
        </header>

        @if (session('status'))
            <p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950">
                <p class="font-bold">{{ __('The review could not be saved.') }}</p>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="dashboard-kpi-grid mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-6" aria-label="{{ __('Key performance indicators') }}">
            <div class="dashboard-kpi-card min-h-28 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm"><p class="text-xs font-semibold text-slate-500">{{ __('Total users') }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($statistics['totalUsers']) }}</p><p class="mt-1 text-[11px] text-slate-500">{{ __(':count active', ['count' => number_format($statistics['activeUsers'])]) }}</p></div>
            <div class="dashboard-kpi-card min-h-28 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm"><p class="text-xs font-semibold text-slate-500">{{ __('Bookings') }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($statistics['totalBookings']) }}</p><p class="mt-1 text-[11px] text-slate-500">{{ __(':completed completed · :cancelled cancelled', ['completed' => number_format($statistics['completedBookings']), 'cancelled' => number_format($statistics['cancelledBookings'])]) }}</p></div>
            <div class="dashboard-kpi-card min-h-28 rounded-2xl bg-ink p-5 text-white shadow-sm"><p class="text-xs font-semibold text-white/70">{{ __('Revenue collected') }}</p><p class="mt-2 text-2xl font-black">KES {{ number_format($statistics['revenue']) }}</p><p class="mt-1 text-[11px] text-white/70">{{ __('Successful payments') }}</p></div>
            <div class="dashboard-kpi-card min-h-28 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm"><p class="text-xs font-semibold text-slate-500">{{ __('Havenedge Tourlink commission') }}</p><p class="mt-2 text-2xl font-black text-slate-950">KES {{ number_format($statistics['tourlinkCommission']) }}</p><p class="mt-1 text-[11px] text-slate-500">{{ __('From paid bookings') }}</p></div>
            <div class="dashboard-kpi-card min-h-28 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm"><p class="text-xs font-semibold text-slate-500">{{ __('Marketplace supply') }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($statistics['totalTrips'] + $statistics['totalVehicles']) }}</p><p class="mt-1 text-[11px] text-slate-500">{{ __(':trips trips · :vehicles vehicles', ['trips' => number_format($statistics['totalTrips']), 'vehicles' => number_format($statistics['totalVehicles'])]) }}</p></div>
            <div class="dashboard-kpi-card min-h-28 rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm"><p class="text-xs font-semibold text-orange-900">{{ __('Pending verification') }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($statistics['pendingVerification']) }}</p><a href="{{ route('admin.verification.index') }}" class="mt-1 inline-block text-[11px] font-bold text-orange-900 hover:underline">{{ __('Open review queue') }}</a></div>
        </section>

        <section class="mt-8 grid gap-5 xl:grid-cols-[minmax(0,1.7fr)_minmax(18rem,0.8fr)]" aria-label="{{ __('Revenue and recent activity') }}">
            <div class="border border-slate-200 bg-white p-4 sm:p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-slate-100 pb-3">
                    <div><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">{{ __('Cash flow') }}</p><h2 class="mt-1 text-lg font-extrabold text-slate-950">{{ __('Revenue over time') }}</h2></div>
                    <p class="text-sm font-bold text-slate-700">{{ __('KES :amount total', ['amount' => number_format($statistics['revenue'])]) }}</p>
                </div>
                <x-admin-bar-chart :title="__('Revenue over time · 12 months')" :labels="$revenueChart['labels']" :values="$revenueChart['values']" format="currency" />
            </div>

            <section class="border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="recent-activities-heading">
                <div class="flex items-baseline justify-between gap-3 border-b border-slate-100 pb-3">
                    <div><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">{{ __('Audit trail') }}</p><h2 id="recent-activities-heading" class="mt-1 text-lg font-extrabold text-slate-950">{{ __('Recent activities') }}</h2></div>
                    <a href="{{ route('admin.audit.index') }}" class="shrink-0 text-xs font-bold text-emerald-800 hover:underline">{{ __('View all') }}</a>
                </div>
                <div class="mt-1">
                    @forelse ($recentActivities as $activity)
                        <article class="border-b border-slate-100 py-3 last:border-0">
                            <p class="text-sm font-semibold text-slate-900">{{ str($activity->action)->replace('.', ' ')->replace('_', ' ')->title() }}</p>
                            <div class="mt-1 flex justify-between gap-3 text-[11px] text-slate-500"><span class="truncate">{{ $activity->admin?->name ?? __('Unknown admin') }} · {{ $activity->entity }}</span><time class="shrink-0" datetime="{{ $activity->created_at?->toIso8601String() }}">{{ $activity->created_at?->locale(app()->getLocale())->diffForHumans() }}</time></div>
                        </article>
                    @empty
                        <p class="py-8 text-sm text-slate-600">{{ __('No administrative activity recorded yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </section>

        <section class="mt-8" aria-labelledby="activity-trends-heading">
            <div class="flex items-end justify-between gap-4 border-b border-slate-200 pb-3"><div><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">{{ __('Demand') }}</p><h2 id="activity-trends-heading" class="mt-1 text-xl font-extrabold text-slate-950">{{ __('Booking activity') }}</h2></div><span class="text-xs text-slate-500">{{ __('Last 12 months') }}</span></div>
            <div class="mt-4 border border-slate-200 bg-white p-4 sm:p-5"><x-admin-bar-chart :title="__('Bookings over time · 12 months')" :labels="$bookingChart['labels']" :values="$bookingChart['values']" /></div>
        </section>

        <section class="mt-8" aria-labelledby="rankings-heading">
            <div class="border-b border-slate-200 pb-3"><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">{{ __('Marketplace') }}</p><h2 id="rankings-heading" class="mt-1 text-xl font-extrabold text-slate-950">{{ __('What is moving') }}</h2></div>
            <div class="mt-4 grid gap-x-8 gap-y-7 md:grid-cols-2">
                <x-admin-bar-chart :title="__('Popular destinations · bookings')" :labels="$popularDestinations['labels']" :values="$popularDestinations['values']" />
                <x-admin-bar-chart title="Most booked trips" :labels="$topTrips['labels']" :values="$topTrips['values']" />
                <x-admin-bar-chart title="Most booked vehicles" :labels="$topVehicles['labels']" :values="$topVehicles['values']" />
                <div>
                    <x-admin-bar-chart :title="__('Operator performance · bookings')" :labels="$operatorPerformance['labels']" :values="$operatorPerformance['values']" />
                    @if (count($operatorPerformance['labels']) > 0)
                        <ul class="divide-y divide-slate-100 text-xs text-slate-600">
                            @foreach ($operatorPerformance['labels'] as $index => $operatorName)
                                <li class="flex justify-between gap-3 py-2"><span>{{ $operatorName }}</span><span class="whitespace-nowrap">{{ __('KES :amount booked', ['amount' => number_format($operatorPerformance['amounts'][$index])]) }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </section>

        <section class="mt-8 border-y border-slate-200 py-5" aria-labelledby="platform-health-heading">
            <div class="flex items-baseline justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">{{ __('Platform health') }}</p><h2 id="platform-health-heading" class="mt-1 text-xl font-extrabold text-slate-950">{{ __('Supply, trust and payouts') }}</h2></div><a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-emerald-800 hover:underline">{{ __('Manage users') }}</a></div>
            <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-3 xl:grid-cols-6">
                <div><dt class="text-xs font-semibold text-slate-500">{{ __('Tour operators') }}</dt><dd class="mt-1 text-xl font-black text-slate-950">{{ number_format($statistics['tourOperators']) }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">{{ __('Vehicle owners') }}</dt><dd class="mt-1 text-xl font-black text-slate-950">{{ number_format($statistics['vehicleOwners']) }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">{{ __('Average rating') }}</dt><dd class="mt-1 text-xl font-black text-slate-950">{{ number_format($statistics['averageRating'], 1) }}<span class="ml-1 text-xs font-semibold text-slate-500">/ 5</span></dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">{{ __('Operator payouts') }}</dt><dd class="mt-1 text-base font-black text-slate-950">KES {{ number_format($statistics['operatorPayouts']) }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">{{ __('Vehicle owner payouts') }}</dt><dd class="mt-1 text-base font-black text-slate-950">KES {{ number_format($statistics['vehicleOwnerPayouts']) }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">{{ __('Booking completion') }}</dt><dd class="mt-1 text-xl font-black text-slate-950">{{ number_format($statistics['completedBookings']) }}<span class="ml-1 text-xs font-semibold text-slate-500">{{ __('completed') }}</span></dd></div>
            </dl>
        </section>

        <section class="mt-8" aria-labelledby="pending-trips-heading">
            <div class="flex items-baseline justify-between gap-4 border-b border-slate-200 pb-3">
                <h2 id="pending-trips-heading" class="text-xl font-extrabold text-slate-950">{{ __('Trips awaiting review') }}</h2>
                <span class="text-sm text-slate-500">{{ __(':count pending', ['count' => $pendingTripCount]) }}</span>
            </div>
            @forelse ($pendingTrips as $trip)
                <article class="grid gap-4 border-b border-slate-200 py-5 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <h3 class="font-bold text-slate-900">{{ $trip->name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $trip->operator?->name ?? __('Operator unavailable') }} · {{ $trip->destination?->name ?? __('Destination not set') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __(':days days · KES :price per person', ['days' => $trip->duration_days, 'price' => number_format($trip->price_per_person)]) }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.trips.update', $trip) }}" class="grid gap-2 sm:grid-cols-[minmax(150px,1fr)_minmax(180px,1.5fr)_auto]">
                        @csrf
                        @method('PATCH')
                        <label class="sr-only" for="trip-status-{{ $trip->id }}">{{ __('Trip review decision') }}</label>
                        <select id="trip-status-{{ $trip->id }}" name="verification_status" required class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm text-slate-900">
                            <option value="APPROVED">{{ __('Approve and publish') }}</option>
                            <option value="REJECTED">{{ __('Reject') }}</option>
                            <option value="MORE_INFO">{{ __('Request more information') }}</option>
                        </select>
                        <label class="sr-only" for="trip-note-{{ $trip->id }}">{{ __('Review note') }}</label>
                        <input id="trip-note-{{ $trip->id }}" name="approval_note" maxlength="2000" placeholder="{{ __('Optional note') }}" class="min-h-10 rounded border border-slate-300 px-3 text-sm">
                        <button class="min-h-10 rounded bg-emerald-800 px-4 text-sm font-bold text-white hover:bg-emerald-900">{{ __('Save review') }}</button>
                    </form>
                </article>
            @empty
                <p class="py-6 text-sm text-slate-600">{{ __('No trips are waiting for review.') }}</p>
            @endforelse
        </section>

        <section class="mt-10" aria-labelledby="pending-vehicles-heading">
            <div class="flex items-baseline justify-between gap-4 border-b border-slate-200 pb-3">
                <h2 id="pending-vehicles-heading" class="text-xl font-extrabold text-slate-950">{{ __('Vehicles awaiting review') }}</h2>
                <span class="text-sm text-slate-500">{{ __(':count pending', ['count' => $pendingVehicleCount]) }}</span>
            </div>
            @forelse ($pendingVehicles as $vehicle)
                <article class="grid gap-4 border-b border-slate-200 py-5 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <h3 class="font-bold text-slate-900">{{ $vehicle->name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $vehicle->owner?->name ?? __('Owner unavailable') }} · {{ $vehicle->destination?->name ?? $vehicle->location }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __(':registration · :seats seats · KES :price per day', ['registration' => $vehicle->registration_number, 'seats' => $vehicle->seating_capacity, 'price' => number_format($vehicle->price_per_day)]) }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.vehicles.update', $vehicle) }}" class="grid gap-2 sm:grid-cols-[minmax(150px,1fr)_minmax(180px,1.5fr)_auto]">
                        @csrf
                        @method('PATCH')
                        <label class="sr-only" for="vehicle-status-{{ $vehicle->id }}">{{ __('Vehicle review decision') }}</label>
                        <select id="vehicle-status-{{ $vehicle->id }}" name="verification_status" required class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm text-slate-900">
                            <option value="APPROVED">{{ __('Approve and publish') }}</option>
                            <option value="REJECTED">{{ __('Reject') }}</option>
                            <option value="MORE_INFO">{{ __('Request more information') }}</option>
                        </select>
                        <label class="sr-only" for="vehicle-note-{{ $vehicle->id }}">{{ __('Review note') }}</label>
                        <input id="vehicle-note-{{ $vehicle->id }}" name="approval_note" maxlength="2000" placeholder="{{ __('Optional note') }}" class="min-h-10 rounded border border-slate-300 px-3 text-sm">
                        <button class="min-h-10 rounded bg-emerald-800 px-4 text-sm font-bold text-white hover:bg-emerald-900">{{ __('Save review') }}</button>
                    </form>
                </article>
            @empty
                <p class="py-6 text-sm text-slate-600">{{ __('No vehicles are waiting for review.') }}</p>
            @endforelse
        </section>
        </div>
    </div>
@endsection
