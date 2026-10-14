@extends('layouts.app')

@section('title', $trip->name.' | Havenedge Tourlink')
@section('content')
@php
    $isPublicValue = static fn (mixed $value): bool => is_string($value)
        && trim($value) !== ''
        && strtoupper(trim($value)) !== 'NULL'
        && strtoupper(trim($value)) !== 'NO';
    $gallery = $trip->images->filter(fn ($image) => $isPublicValue($image->url))->values();
    $destinationImage = $trip->destination?->image_url;
    $heroImage = $gallery->first()?->url ?? ($isPublicValue($destinationImage) ? $destinationImage : null);
    $highlights = collect($trip->highlights ?? [])->filter($isPublicValue)->values();
    $includedItems = collect($trip->included_items ?? [])->filter($isPublicValue)->values();
    $excludedItems = collect($trip->excluded_items ?? [])->filter($isPublicValue)->values();
    $itinerary = collect($trip->itinerary ?? [])->filter(fn ($step) => is_array($step)
        && ($isPublicValue($step['title'] ?? null)
            || $isPublicValue($step['location'] ?? null)
            || $isPublicValue($step['detail'] ?? null)))->values();
    $charges = collect($trip->additional_charges ?? [])->filter(fn ($charge) => is_array($charge)
        && $isPublicValue($charge['name'] ?? null))->values();
    $operatorName = $trip->operator?->operatorProfile?->company_name
        ?: $trip->operator?->name
        ?: __('Tourlink provider');
    $verification = $trip->operator?->verification_level?->value ?? 'BASIC';
    $departureDate = $trip->departure_date
        ->timezone(config('app.display_timezone'))
        ->locale(app()->getLocale())
        ->translatedFormat('M j, Y');
@endphp

<section class="trip-detail-page">
    <div class="container-page">
        <nav class="trip-detail-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('trips.index') }}">{{ __('Find a trip') }}</a>
            <span aria-hidden="true">/</span>
            @if($trip->destination)
                <a href="{{ route('trips.index', ['destination' => $trip->destination->slug]) }}">{{ $trip->destination->name }}</a>
                <span aria-hidden="true">/</span>
            @endif
            <span aria-current="page">{{ $trip->name }}</span>
        </nav>

        <header class="trip-detail-heading">
            <div class="trip-detail-heading__copy">
                @if($trip->category)
                    <p class="trip-detail-eyebrow">{{ $trip->category->name }}</p>
                @endif
                <h1>{{ $trip->name }}</h1>
                <div class="trip-detail-route">
                    <span class="trip-detail-route__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="6" r="2.5"/><path d="M8.5 18H12a3 3 0 0 0 3-3V9a3 3 0 0 1 3-3"/></svg>
                    </span>
                    <span>{{ $trip->starting_point }} <span aria-hidden="true">→</span> {{ $trip->ending_point }}</span>
                </div>
            </div>
            <x-offline-favorite :trip="$trip" />
        </header>

        <section class="trip-detail-gallery" aria-label="{{ __('Trip photos') }}">
            <div class="trip-detail-gallery__main {{ $heroImage ? 'has-image' : 'is-placeholder' }}">
                @if($heroImage)
                    <img src="{{ $heroImage }}" alt="{{ $gallery->first()?->alt ?: $trip->name }}" width="1600" height="1000" fetchpriority="high">
                @else
                    <div class="trip-detail-gallery__placeholder" aria-hidden="true">
                        <span>HT</span>
                    </div>
                @endif
                <div class="trip-detail-gallery__shade"></div>
                <div class="trip-detail-gallery__content">
                    <span class="trip-detail-gallery__badge">{{ __('A journey to remember') }}</span>
                    <p>{{ $trip->destination?->name ?? $trip->ending_point }}</p>
                    <h2>{{ $trip->name }}</h2>
                    <div class="trip-detail-gallery__facts">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                            {{ $departureDate }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            {{ __(':days days', ['days' => $trip->duration_days]) }}
                        </span>
                        @if($trip->reviews_count > 0)
                            <span>
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2.8 2.8 5.7 6.3.9-4.6 4.5 1.1 6.3-5.6-3-5.6 3 1.1-6.3-4.6-4.5 6.3-.9L12 2.8Z"/></svg>
                                {{ number_format((float) $trip->reviews_avg_rating, 1) }} <span class="trip-detail-rating-count">({{ $trip->reviews_count }})</span>
                            </span>
                        @endif
                    </div>
                </div>
                @if($gallery->isNotEmpty())
                    <span class="trip-detail-gallery__image-count">{{ $gallery->count() }} {{ __('photos') }}</span>
                @else
                    <span class="trip-detail-gallery__image-count">{{ __('Trip preview') }}</span>
                @endif
            </div>
            @if($gallery->count() > 1)
                <div class="trip-detail-gallery__side">
                    @foreach($gallery->skip(1)->take(2) as $image)
                        <img src="{{ $image->url }}" alt="{{ $image->alt ?: $trip->name }}" width="800" height="500" loading="lazy" decoding="async">
                    @endforeach
                    @if($gallery->count() === 2)
                        <div class="trip-detail-gallery__side-note">
                            <span>{{ __('Hosted by') }}</span>
                            <strong>{{ $operatorName }}</strong>
                        </div>
                    @endif
                </div>
            @endif
        </section>

        <div class="trip-detail-layout">
            <article class="trip-detail-content">
                <section class="trip-detail-card trip-detail-about">
                    <p class="trip-detail-section-label">{{ __('The experience') }}</p>
                    <h2>{{ __('About this trip') }}</h2>
                    @if($isPublicValue($trip->description))
                        <div class="trip-detail-description">{!! \Illuminate\Support\Str::markdown($trip->description, ['html_input' => 'strip']) !!}</div>
                    @else
                        <p class="trip-detail-muted">{{ __('More details about this trip will be available soon.') }}</p>
                    @endif
                    <div class="trip-detail-host">
                        <span class="trip-detail-host__mark" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($operatorName, 0, 1)) }}</span>
                        <span class="trip-detail-host__copy">
                            <span>{{ __('Your host') }}</span>
                            <strong>{{ $operatorName }}</strong>
                        </span>
                        <span class="trip-detail-verification">{{ $verification === 'TRUSTED' ? __('Trusted host') : __(':level provider', ['level' => ucfirst(strtolower($verification))]) }}</span>
                    </div>
                </section>

                @if($highlights->isNotEmpty())
                    <section class="trip-detail-card">
                        <p class="trip-detail-section-label">{{ __('Good to know') }}</p>
                        <h2>{{ __('Trip highlights') }}</h2>
                        <ul class="trip-detail-highlights">
                            @foreach($highlights as $highlight)
                                <li><span aria-hidden="true">✓</span>{{ $highlight }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($itinerary->isNotEmpty())
                    <section class="trip-detail-card">
                        <p class="trip-detail-section-label">{{ __('Plan your day') }}</p>
                        <h2>{{ __('Itinerary') }}</h2>
                        <ol class="trip-detail-itinerary">
                            @foreach($itinerary as $step)
                                <li>
                                    <span class="trip-detail-itinerary__marker" aria-hidden="true">{{ str_pad((string) ($step['day'] ?? $loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
                                    <div class="trip-detail-itinerary__body">
                                        <p class="trip-detail-itinerary__time">
                                            {{ __('Day :day', ['day' => $step['day'] ?? $loop->iteration]) }}
                                            @if($isPublicValue($step['start_time'] ?? null)) · {{ $step['start_time'] }}@endif
                                            @if($isPublicValue($step['end_time'] ?? null)) – {{ $step['end_time'] }}@endif
                                        </p>
                                        @if($isPublicValue($step['title'] ?? null))
                                            <h3>{{ $step['title'] }}</h3>
                                        @endif
                                        @if($isPublicValue($step['location'] ?? null))
                                            <p class="trip-detail-itinerary__location">{{ $step['location'] }}</p>
                                        @endif
                                        @if($isPublicValue($step['detail'] ?? null))
                                            <p class="trip-detail-itinerary__detail">{{ $step['detail'] }}</p>
                                        @endif
                                        @if($isPublicValue($step['meals'] ?? null))
                                            <p class="trip-detail-itinerary__meal"><strong>{{ __('Meals') }}:</strong> {{ $step['meals'] }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if($includedItems->isNotEmpty() || $excludedItems->isNotEmpty())
                    <section class="trip-detail-card">
                        <p class="trip-detail-section-label">{{ __('Before you go') }}</p>
                        <h2>{{ __('What to expect') }}</h2>
                        <div class="trip-detail-inclusions">
                            @if($includedItems->isNotEmpty())
                                <div>
                                    <h3>{{ __('Included') }}</h3>
                                    <ul>
                                        @foreach($includedItems as $item)
                                            <li><span aria-hidden="true">✓</span>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if($excludedItems->isNotEmpty())
                                <div class="trip-detail-exclusions">
                                    <h3>{{ __('Not included') }}</h3>
                                    <ul>
                                        @foreach($excludedItems as $item)
                                            <li><span aria-hidden="true">−</span>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

                @if($isPublicValue($trip->cancellation_policy))
                    <section class="trip-detail-card trip-detail-cancellation">
                        <span class="trip-detail-cancellation__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                        </span>
                        <div>
                            <p class="trip-detail-section-label">{{ __('Travel with confidence') }}</p>
                            <h2>{{ __('Cancellation policy') }}</h2>
                            <p>{{ $trip->cancellation_policy }}</p>
                        </div>
                    </section>
                @endif
            </article>

            <aside class="trip-detail-booking" aria-label="{{ __('Trip booking details') }}">
                <div class="trip-detail-booking__topline">
                    <span>{{ __('Your trip') }}</span>
                    <span class="trip-detail-booking__availability"><i aria-hidden="true"></i>{{ __(':seats seats left', ['seats' => $trip->available_seats]) }}</span>
                </div>
                <p class="trip-detail-booking__price">KES {{ number_format($trip->price_per_person) }} <span>/ {{ __('person') }}</span></p>
                @if($trip->child_price !== null)
                    <p class="trip-detail-booking__child">{{ __('Child price') }} <strong>KES {{ number_format($trip->child_price) }}</strong></p>
                @endif
                @if($trip->deposit_amount !== null)
                    <p class="trip-detail-booking__deposit">{{ __('Booking deposit') }} <strong>KES {{ number_format($trip->deposit_amount) }}</strong></p>
                @endif

                <div class="trip-detail-booking__facts">
                    <div>
                        <span class="trip-detail-booking__fact-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                        </span>
                        <span><small>{{ __('Departure') }}</small><strong>{{ $departureDate }}</strong></span>
                    </div>
                    @if($trip->transport_type)
                        <div>
                            <span class="trip-detail-booking__fact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14l1-6-2-5H6l-2 5 1 6ZM4 11h16M7 17v2m10-2v2"/><circle cx="7.5" cy="14" r="1"/><circle cx="16.5" cy="14" r="1"/></svg>
                            </span>
                            <span><small>{{ __('Transport') }}</small><strong>{{ str_replace('_', ' ', $trip->transport_type) }}</strong></span>
                        </div>
                    @endif
                    @if($isPublicValue($trip->meeting_point))
                        <div>
                            <span class="trip-detail-booking__fact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            </span>
                            <span><small>{{ __('Meeting point') }}</small><strong>{{ $trip->meeting_point }}</strong></span>
                        </div>
                    @endif
                </div>

                @if($charges->isNotEmpty())
                    <div class="trip-detail-booking__charges">
                        <p>{{ __('Additional charges') }}</p>
                        @foreach($charges as $charge)
                            <div><span>{{ $charge['name'] }}</span><strong>KES {{ number_format((int) ($charge['amount'] ?? 0)) }}</strong></div>
                        @endforeach
                    </div>
                @endif

                @if($trip->available_seats > 0)
                    <form class="trip-detail-booking__form" method="POST" action="{{ route('bookings.trips.store') }}">
                        @csrf
                        <input type="hidden" name="trip" value="{{ $trip->id }}">
                        <label>
                            {{ __('Travelers') }}
                            <input type="number" name="travelers" min="1" max="{{ min(100, $trip->available_seats) }}" value="1" required>
                        </label>
                        <label>
                            {{ __('Pickup location') }}
                            <input type="text" name="pickup_location" maxlength="120" placeholder="{{ __('Enter your preferred pickup point') }}">
                        </label>
                        <input type="hidden" name="start_date" value="{{ $trip->departure_date->toDateTimeString() }}">
                        <button type="submit">{{ __('Request this trip') }} <span aria-hidden="true">→</span></button>
                    </form>
                    <p class="trip-detail-booking__secure">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                        {{ __('No payment is taken until your request is confirmed.') }}
                    </p>
                @else
                    <p class="trip-detail-booking__sold-out">{{ __('This trip is currently fully booked.') }}</p>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
