@extends('layouts.app')

@section('title', $destination->name.' | Havenedge Tourlink')
@section('meta_description', $destination->description ?: __('Explore trips in :destination.', ['destination' => $destination->name]))
@section('content')
@php
    $attractions = collect($destination->attractions ?? [])->filter(fn ($item) => is_string($item) && trim($item) !== '')->values();
    $activities = collect($destination->activities ?? [])->filter(fn ($item) => is_string($item) && trim($item) !== '')->values();
@endphp
<article class="destination-redesign">
    <header class="destination-redesign-hero">
        <div class="container-page destination-redesign-hero__grid">
            <div class="destination-redesign-hero__copy">
                <a class="destination-redesign-back" href="{{ route('home') }}#destinations">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
                    {{ __('All destinations') }}
                </a>
                <p class="destination-redesign-kicker"><span></span>{{ $destination->country ?: __('Discover') }}</p>
                <h1>{{ $destination->name }}</h1>
                @if($destination->location)
                    <p class="destination-redesign-location">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        {{ $destination->location }}
                    </p>
                @endif
                @if($destination->description)
                    <p class="destination-redesign-description">{{ $destination->description }}</p>
                @else
                    <p class="destination-redesign-description">{{ __('Discover trips, local experiences and places to explore in :destination.', ['destination' => $destination->name]) }}</p>
                @endif
                <div class="destination-redesign-actions">
                    <a class="destination-redesign-button" href="#destination-trips">{{ __('Explore trips') }} <span aria-hidden="true">↓</span></a>
                    <span class="destination-redesign-count">{{ $trips->total() }} {{ trans_choice('trip|trips', $trips->total()) }} {{ __('to explore') }}</span>
                </div>
                <div class="destination-redesign-summary">
                    @if($attractions->isNotEmpty())
                        <span><b aria-hidden="true">✦</b>{{ $attractions->count() }} {{ __('local highlights') }}</span>
                    @endif
                    @if($activities->isNotEmpty())
                        <span><b aria-hidden="true">↗</b>{{ $activities->count() }} {{ __('things to do') }}</span>
                    @endif
                    @if($attractions->isEmpty() && $activities->isEmpty())
                        <span><b aria-hidden="true">✦</b>{{ __('A place to discover') }}</span>
                    @endif
                </div>
            </div>

            <figure class="destination-redesign-photo">
                @if($destination->image_url)
                    <img src="{{ $destination->image_url }}" alt="{{ __('Explore :destination', ['destination' => $destination->name]) }}" fetchpriority="high" decoding="async">
                @else
                    <div class="destination-redesign-photo__fallback" aria-hidden="true">
                        <svg viewBox="0 0 120 100" fill="none"><path d="m8 84 31-45 19 27 15-20 39 38H8Z" fill="currentColor" opacity=".2"/><path d="m8 84 31-45 19 27 15-20 39 38H8Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><circle cx="88" cy="26" r="11" stroke="currentColor" stroke-width="3"/><path d="M15 92h90" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    </div>
                @endif
                <span class="destination-redesign-photo__wash" aria-hidden="true"></span>
                <figcaption>
                    <span>{{ __('Your next journey starts here.') }}</span>
                    <small>{{ $destination->name }}@if($destination->country) · {{ $destination->country }}@endif</small>
                </figcaption>
                <span class="destination-redesign-photo__stamp" aria-hidden="true">{{ __('Go') }}<br>{{ __('explore') }}</span>
            </figure>
        </div>
    </header>

    @if($attractions->isNotEmpty() || $activities->isNotEmpty())
        <section class="destination-redesign-highlights">
            <div class="container-page">
                <header class="destination-redesign-section-heading">
                    <div>
                        <p class="destination-redesign-kicker"><span></span>{{ __('Get to know the area') }}</p>
                        <h2>{{ __('A few ways to make it yours.') }}</h2>
                    </div>
                    <p>{{ __('Find inspiration for your visit, then browse trips that bring you closer to the places and experiences you came for.') }}</p>
                </header>
                <div class="destination-redesign-highlight-grid">
                    @if($attractions->isNotEmpty())
                        <section class="destination-redesign-highlight-card" aria-labelledby="destination-highlights-title">
                            <div class="destination-redesign-highlight-card__heading">
                                <span aria-hidden="true">✦</span>
                                <div><p>{{ __('Start exploring') }}</p><h3 id="destination-highlights-title">{{ __('Local highlights') }}</h3></div>
                            </div>
                            <ul>@foreach($attractions as $attraction)<li><span aria-hidden="true">↗</span>{{ $attraction }}</li>@endforeach</ul>
                        </section>
                    @endif
                    @if($activities->isNotEmpty())
                        <section class="destination-redesign-highlight-card" aria-labelledby="destination-activities-title">
                            <div class="destination-redesign-highlight-card__heading">
                                <span aria-hidden="true">⌁</span>
                                <div><p>{{ __('Make the most of it') }}</p><h3 id="destination-activities-title">{{ __('Things to do') }}</h3></div>
                            </div>
                            <ul>@foreach($activities as $activity)<li><span aria-hidden="true">↗</span>{{ $activity }}</li>@endforeach</ul>
                        </section>
                    @endif
                </div>
            </div>
        </section>
    @endif
</article>

<section id="destination-trips" class="container-page destination-redesign-trips" aria-labelledby="destination-trips-heading">
    <header class="destination-redesign-trips__heading">
        <div>
            <p class="destination-redesign-kicker"><span></span>{{ __('Plan your visit') }}</p>
            <h2 id="destination-trips-heading">{{ __('Trips in :destination', ['destination' => $destination->name]) }}</h2>
            <p>{{ __('Browse experiences and find a journey that feels right for you.') }}</p>
        </div>
        <span class="destination-redesign-trips__count">{{ $trips->total() }} {{ trans_choice('experience|experiences', $trips->total()) }}</span>
    </header>
    @if($trips->isEmpty())
        <div class="destination-redesign-empty">
            <span aria-hidden="true">✳</span>
            <h3>{{ __('Trips are coming soon') }}</h3>
            <p>{{ __('There are no published trips for this destination yet. Explore all available trips in the meantime.') }}</p>
            <a href="{{ route('trips.index') }}">{{ __('Browse all trips') }} <span aria-hidden="true">→</span></a>
        </div>
    @else
        <div class="destination-redesign-trips__grid">
            @foreach($trips as $trip)
                <x-trip-card :trip="$trip" />
            @endforeach
        </div>
        <div class="destination-redesign-pagination">{{ $trips->links() }}</div>
    @endif
</section>
@endsection
