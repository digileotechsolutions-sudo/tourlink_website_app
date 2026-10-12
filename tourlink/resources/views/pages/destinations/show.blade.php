@extends('layouts.app')

@section('title', $destination->name.' | Havenedge Tourlink')
@section('meta_description', $destination->description ?: __('Explore trips in :destination.', ['destination' => $destination->name]))
@section('content')
<article>
    <header class="destination-detail-hero">
        <div class="container-page destination-detail-hero__layout">
            <div class="destination-detail-copy">
                <a class="destination-detail-back" href="{{ route('home') }}#destinations"><span aria-hidden="true">←</span>{{ __('All destinations') }}</a>
                <p class="destination-detail-eyebrow">{{ $destination->country ?: __('Discover') }}</p>
                <h1>{{ $destination->name }}</h1>
                @if($destination->location)<p class="destination-detail-location"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>{{ $destination->location }}</p>@endif
                @if($destination->description)<p class="destination-detail-description">{{ $destination->description }}</p>@endif
                <a class="destination-detail-action" href="#destination-trips">{{ __('Explore trips') }}<span aria-hidden="true">↓</span></a>
            </div>
            <figure class="destination-detail-image">
                @if($destination->image_url)
                    <img src="{{ $destination->image_url }}" alt="{{ $destination->name }}" fetchpriority="high" decoding="async">
                @else
                    <div class="destination-detail-image__fallback" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 20 6-10 4 6 3-4 5 8H3Z"></path><circle cx="16.5" cy="6.5" r="2"></circle></svg></div>
                @endif
                <figcaption>{{ $destination->name }}<span>{{ $trips->total() }} {{ __('trips') }}</span></figcaption>
            </figure>
        </div>
    </header>

    @if(!empty($destination->attractions) || !empty($destination->activities))
        <section class="container-page destination-detail-highlights" aria-label="{{ __('Destination highlights') }}">
            @if(!empty($destination->attractions))
                <div class="destination-detail-list">
                    <p>{{ __('Highlights') }}</p>
                    <ul>@foreach($destination->attractions as $attraction)<li>{{ $attraction }}</li>@endforeach</ul>
                </div>
            @endif
            @if(!empty($destination->activities))
                <div class="destination-detail-list">
                    <p>{{ __('Things to do') }}</p>
                    <ul>@foreach($destination->activities as $activity)<li>{{ $activity }}</li>@endforeach</ul>
                </div>
            @endif
        </section>
    @endif
</article>

<section id="destination-trips" class="container-page destination-trips-section" aria-labelledby="destination-trips-heading">
    <div class="destination-trips-heading">
        <div>
            <p>{{ __('Plan your visit') }}</p>
            <h2 id="destination-trips-heading">{{ __('Trips in :destination', ['destination' => $destination->name]) }}</h2>
        </div>
        <span>{{ $trips->total() }} {{ __('experiences') }}</span>
    </div>
    @if($trips->isEmpty())
        <div class="destination-trips-empty">
            <h3>{{ __('Trips are coming soon') }}</h3>
            <p>{{ __('There are no published trips for this destination yet. Explore all available trips in the meantime.') }}</p>
            <a href="{{ route('trips.index') }}">{{ __('Browse all trips') }}<span aria-hidden="true">→</span></a>
        </div>
    @else
        <div class="destination-trips-grid">
            @foreach($trips as $trip)
                <x-trip-card :trip="$trip" />
            @endforeach
        </div>
        <div class="destination-trips-pagination">{{ $trips->links() }}</div>
    @endif
</section>
@endsection