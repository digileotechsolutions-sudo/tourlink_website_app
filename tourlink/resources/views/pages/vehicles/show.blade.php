@extends('layouts.app')

@section('title', $vehicle->name.' | Havenedge Tourlink')
@section('content')
@php
    $isPublicValue = static fn (mixed $value): bool => is_string($value)
        && trim($value) !== ''
        && strtoupper(trim($value)) !== 'NULL'
        && strtoupper(trim($value)) !== 'NO';
    $gallery = $vehicle->images->filter(fn ($image) => $isPublicValue($image->url))->values();
    $destinationImage = $vehicle->destination?->image_url;
    $heroImage = $gallery->first()?->url ?? ($isPublicValue($destinationImage) ? $destinationImage : null);
    $ownerName = $vehicle->owner?->vehicleOwnerProfile?->business_name
        ?: $vehicle->owner?->name
        ?: __('Vehicle owner');
    $ownerLogo = $vehicle->owner?->vehicleOwnerProfile?->logo_url;
    $ownerLogo = $isPublicValue($ownerLogo) ? $ownerLogo : null;
    $verification = $vehicle->owner?->verification_level?->value ?? 'BASIC';
    $currency = $vehicle->currency ?: 'KES';
    $charges = collect($vehicle->additional_charges ?? [])->filter(fn ($charge) => is_array($charge)
        && $isPublicValue($charge['name'] ?? null))->values();
    $specifications = [
        __('Vehicle type') => $vehicle->body_type,
        __('Year') => $vehicle->year,
        __('Seats') => $vehicle->seating_capacity,
        __('Transmission') => $vehicle->transmission,
        __('Fuel type') => $vehicle->fuel_type,
        __('Air conditioning') => $vehicle->air_conditioning ? __('Yes') : __('No'),
        __('Engine') => $vehicle->engine_cc ? number_format($vehicle->engine_cc).' cc' : null,
        __('Colour') => $vehicle->colour,
    ];
    $specifications = collect($specifications)->filter(fn ($value) => $value !== null && $isPublicValue((string) $value));
    $today = now(config('app.display_timezone'))->toDateString();
@endphp

<section class="vehicle-detail-page">
    <div class="container-page">
        <nav class="vehicle-detail-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('vehicles.index') }}">{{ __('Hire a vehicle') }}</a>
            <span aria-hidden="true">/</span>
            @if($vehicle->destination)
                <a href="{{ route('vehicles.index', ['search' => $vehicle->destination->name]) }}">{{ $vehicle->destination->name }}</a>
                <span aria-hidden="true">/</span>
            @endif
            <span aria-current="page">{{ $vehicle->name }}</span>
        </nav>

        <header class="vehicle-detail-heading">
            <div class="vehicle-detail-heading__copy">
                <p class="vehicle-detail-eyebrow">{{ $vehicle->location }}</p>
                <h1>{{ $vehicle->name }}</h1>
                <p class="vehicle-detail-subtitle">
                    {{ $vehicle->make }} {{ $vehicle->model }}
                    @if($isPublicValue($vehicle->body_type)) · {{ $vehicle->body_type }}@endif
                    @if($vehicle->year) · {{ $vehicle->year }}@endif
                </p>
                @if($vehicle->reviews_count > 0)
                    <p class="vehicle-detail-rating">
                        <span aria-hidden="true">★</span>
                        {{ number_format((float) $vehicle->reviews_avg_rating, 1) }}
                        <span>({{ $vehicle->reviews_count }} {{ __('reviews') }})</span>
                    </p>
                @endif
            </div>
            <span class="vehicle-detail-approved">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 7 3v5c0 4.6-3 8-7 10-4-2-7-5.4-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                {{ __('Verified listing') }}
            </span>
        </header>

        <section class="vehicle-detail-gallery" aria-label="{{ __('Vehicle photos') }}">
            <div class="vehicle-detail-gallery__main {{ $heroImage ? 'has-image' : 'is-placeholder' }}">
                @if($heroImage)
                    <img src="{{ $heroImage }}" alt="{{ $gallery->first()?->alt ?: $vehicle->name }}" width="1600" height="1000" fetchpriority="high">
                @else
                    <div class="vehicle-detail-gallery__placeholder" aria-hidden="true">
                        <svg viewBox="0 0 120 80" fill="none"><path d="M17 48 25 29c2-5 6-8 12-8h39c5 0 9 2 12 7l12 20 7 4v12H13V51l4-3Z" fill="currentColor" opacity=".23"/><path d="m25 30-8 18h78L83 29c-2-4-5-6-10-6H37c-5 0-9 2-12 7ZM13 51h94v13H13V51Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><circle cx="31" cy="63" r="8" fill="#fff" stroke="currentColor" stroke-width="3"/><circle cx="89" cy="63" r="8" fill="#fff" stroke="currentColor" stroke-width="3"/><path d="M37 25h36" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    </div>
                @endif
                <div class="vehicle-detail-gallery__shade"></div>
                <div class="vehicle-detail-gallery__content">
                    <span class="vehicle-detail-gallery__badge">{{ __('Ready for the road') }}</span>
                    <p>{{ $vehicle->location }}</p>
                    <h2>{{ $vehicle->name }}</h2>
                    <div class="vehicle-detail-gallery__facts">
                        @if($vehicle->seating_capacity)
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2m2-8a3 3 0 1 0 0-6m4 14v-2a6 6 0 0 0-4-5.7"/></svg>{{ __(':seats seats', ['seats' => $vehicle->seating_capacity]) }}</span>
                        @endif
                        @if($vehicle->four_by_four)
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h12l4 5h2v5h-2m-12 0H5v-5h14m-12 5a2 2 0 1 0 4 0m6 0a2 2 0 1 0 4 0M7 7v5"/></svg>4×4</span>
                        @endif
                        @if($vehicle->driver_included)
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2m-7-7v7"/></svg>{{ __('Driver included') }}</span>
                        @endif
                    </div>
                </div>
                @if($gallery->isNotEmpty())
                    <span class="vehicle-detail-gallery__image-count">{{ $gallery->count() }} {{ __('photos') }}</span>
                @else
                    <span class="vehicle-detail-gallery__image-count">{{ __('Vehicle preview') }}</span>
                @endif
            </div>
            @if($gallery->count() > 1)
                <div class="vehicle-detail-gallery__side">
                    @foreach($gallery->skip(1)->take(2) as $image)
                        <img src="{{ $image->url }}" alt="{{ $image->alt ?: $vehicle->name }}" width="800" height="500" loading="lazy" decoding="async">
                    @endforeach
                    @if($gallery->count() === 2)
                        <div class="vehicle-detail-gallery__side-note">
                            <span>{{ __('Provided by') }}</span>
                            <strong>{{ $ownerName }}</strong>
                        </div>
                    @endif
                </div>
            @endif
        </section>

        <div class="vehicle-detail-layout">
            <article class="vehicle-detail-content">
                <section class="vehicle-detail-card">
                    <p class="vehicle-detail-section-label">{{ __('At a glance') }}</p>
                    <h2>{{ __('Vehicle details') }}</h2>
                    <dl class="vehicle-detail-specs">
                        @foreach($specifications as $label => $value)
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>{{ is_string($value) && in_array($value, ['Yes', 'No'], true) ? __($value) : $value }}</dd>
                            </div>
                        @endforeach
                        @if($vehicle->four_by_four)
                            <div><dt>{{ __('Drive') }}</dt><dd>4×4</dd></div>
                        @endif
                        @if($vehicle->driver_included)
                            <div><dt>{{ __('Driver') }}</dt><dd>{{ __('Available') }}</dd></div>
                        @endif
                    </dl>
                    <div class="vehicle-detail-host">
                        @if($ownerLogo)
                            <img class="vehicle-detail-host__logo" src="{{ $ownerLogo }}" alt="{{ $ownerName }} logo" width="48" height="48" loading="lazy" decoding="async">
                        @else
                            <span class="vehicle-detail-host__mark" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($ownerName, 0, 1)) }}</span>
                        @endif
                        <span class="vehicle-detail-host__copy">
                            <span>{{ __('Listed by') }}</span>
                            <strong>{{ $ownerName }}</strong>
                        </span>
                        <span class="vehicle-detail-verification">{{ $verification === 'TRUSTED' ? __('Trusted host') : __(':level provider', ['level' => ucfirst(strtolower($verification))]) }}</span>
                    </div>
                </section>

                @if($vehicle->destination || $isPublicValue($vehicle->location))
                    <section class="vehicle-detail-card vehicle-detail-location">
                        <span class="vehicle-detail-location__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        </span>
                        <div>
                            <p class="vehicle-detail-section-label">{{ __('Where you can collect') }}</p>
                            <h2>{{ $vehicle->location }}</h2>
                            @if($vehicle->destination)
                                <p>{{ __('Available near :destination.', ['destination' => $vehicle->destination->name]) }}</p>
                            @endif
                        </div>
                    </section>
                @endif

                <section class="vehicle-detail-card vehicle-detail-privacy">
                    <span class="vehicle-detail-privacy__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                    </span>
                    <div>
                        <h2>{{ __('Your details stay protected') }}</h2>
                        <p>{{ __('Registration details are shared with confirmed booking participants.') }}</p>
                    </div>
                </section>
            </article>

            <aside class="vehicle-detail-booking" aria-label="{{ __('Vehicle booking details') }}">
                <div class="vehicle-detail-booking__topline">
                    <span>{{ __('Rental details') }}</span>
                    <span class="vehicle-detail-booking__availability"><i aria-hidden="true"></i>{{ __('Request to reserve') }}</span>
                </div>
                <p class="vehicle-detail-booking__price">{{ $currency }} {{ number_format($vehicle->price_per_day) }} <span>/ {{ __('day') }}</span></p>
                @if($vehicle->hourly_price)
                    <p class="vehicle-detail-booking__rate">{{ __('Hourly rate') }} <strong>{{ $currency }} {{ number_format($vehicle->hourly_price) }}</strong></p>
                @endif
                @if($vehicle->trip_price)
                    <p class="vehicle-detail-booking__rate">{{ __('Trip rate') }} <strong>{{ $currency }} {{ number_format($vehicle->trip_price) }}</strong></p>
                @endif
                @if($vehicle->driver_charge)
                    <p class="vehicle-detail-booking__rate">{{ __('Driver charge') }} <strong>{{ $currency }} {{ number_format($vehicle->driver_charge) }} / {{ __('day') }}</strong></p>
                @endif

                <div class="vehicle-detail-booking__features">
                    <span><b aria-hidden="true">✦</b>{{ __(':seats seats', ['seats' => $vehicle->seating_capacity]) }}</span>
                    @if($vehicle->four_by_four)<span><b aria-hidden="true">✓</b>4×4</span>@endif
                    @if($vehicle->driver_included)<span><b aria-hidden="true">✓</b>{{ __('Driver included') }}</span>@endif
                </div>

                @if($charges->isNotEmpty())
                    <div class="vehicle-detail-booking__charges">
                        <p>{{ __('Additional charges') }}</p>
                        @foreach($charges as $charge)
                            <div><span>{{ $charge['name'] }}</span><strong>{{ $currency }} {{ number_format((int) ($charge['amount'] ?? 0)) }}</strong></div>
                        @endforeach
                    </div>
                @endif

                <form class="vehicle-detail-booking__form" method="POST" action="{{ route('bookings.vehicles.store') }}">
                    @csrf
                    <input type="hidden" name="vehicle" value="{{ $vehicle->id }}">
                    <label>
                        {{ __('Pickup date') }}
                        <input type="date" name="start_date" min="{{ $today }}" value="{{ old('start_date') }}" required>
                    </label>
                    <label>
                        {{ __('Return date') }}
                        <input type="date" name="end_date" min="{{ $today }}" value="{{ old('end_date') }}" required>
                    </label>
                    @if($vehicle->driver_included || $vehicle->driver_charge)
                        <label class="vehicle-detail-booking__driver">
                            <input type="checkbox" name="driver_required" value="1">
                            <span>{{ __('Include a driver') }}</span>
                        </label>
                    @endif
                    <button type="submit">{{ __('Request this vehicle') }} <span aria-hidden="true">→</span></button>
                </form>
                <p class="vehicle-detail-booking__secure">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                    {{ __('Your request is confirmed by the vehicle owner before payment.') }}
                </p>
            </aside>
        </div>
    </div>
</section>
@endsection
