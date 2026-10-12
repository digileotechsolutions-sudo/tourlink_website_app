@extends('layouts.app')

@section('title', __('Hire a vehicle').' | Havenedge Tourlink')
@section('content')
<section class="vehicles-hero">
    <div class="container-page">
        <div class="vehicles-hero__eyebrow"><span aria-hidden="true"></span>{{ __('Go your own way') }}</div>
        <div class="vehicles-hero__heading">
            <div>
                <h1>{{ __('The right ride is here.') }}</h1>
                <p>{{ __('Find a vehicle that fits your route, your group and the way you want to travel.') }}</p>
            </div>
            <div class="vehicles-hero__count" aria-label="{{ __(':count vehicles ready to go', ['count' => $vehicles->total()]) }}">
                <span class="vehicles-hero__count-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m5 11 1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11l2 2v5h-2v-2H5v2H3v-5l2-2Z"></path><path d="M5 11h14M7 13h.01M17 13h.01"></path></svg></span>
                <span><strong>{{ $vehicles->total() }}</strong><span>{{ __('vehicles ready to go') }}</span></span>
            </div>
        </div>
        <div class="vehicles-hero__search">
            <x-search-panel mode="vehicles" :search="$filters['search'] ?? ''" />
        </div>
    </div>
</section>
<section class="container-page vehicles-results" aria-labelledby="vehicles-results-heading">
    <div class="vehicles-results-heading">
        <div>
            <p>{{ __('Find your fit') }}</p>
            <h2 id="vehicles-results-heading">{{ __('Vehicles for the journey') }}</h2>
        </div>
        @if(!empty($filters['search']))
            <a href="{{ route('vehicles.index') }}">{{ __('Clear search') }}<span aria-hidden="true">×</span></a>
        @endif
    </div>
    @if($vehicles->isEmpty())
        <div class="vehicles-empty-state">
            <span class="vehicles-empty-state__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m5 11 1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11l2 2v5h-2v-2H5v2H3v-5l2-2Z"></path><path d="M5 11h14M7 13h.01M17 13h.01"></path></svg></span>
            <h2>{{ __('No vehicles match that search') }}</h2>
            <p>{{ __('Try another location or vehicle type.') }}</p>
            <a href="{{ route('vehicles.index') }}">{{ __('Browse all vehicles') }}</a>
        </div>
    @else
        <div class="vehicles-results-grid">
            @foreach($vehicles as $vehicle)
                <x-vehicle-card :vehicle="$vehicle" />
            @endforeach
        </div>
        <div class="vehicles-pagination">{{ $vehicles->links() }}</div>
    @endif
</section>
@endsection
