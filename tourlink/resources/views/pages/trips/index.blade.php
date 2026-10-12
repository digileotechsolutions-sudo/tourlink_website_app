@extends('layouts.app')

@section('title', __('Find a trip').' | Havenedge Tourlink')
@section('content')
<section class="trips-hero">
    <div class="container-page">
        <div class="trips-hero__topline">
            <p class="trips-eyebrow"><span aria-hidden="true"></span>{{ __('Find your next story') }}</p>
            <span class="trips-hero__note">{{ __('Thoughtful trips from trusted local hosts') }}</span>
        </div>
        <h1>{{ __('Trips, made memorable.') }}</h1>
        <p class="trips-hero__description">{{ __('Explore guided journeys, scenic road trips and local experiences, all in one place.') }}</p>
        <div class="trips-hero__search">
            <x-search-panel mode="trips" :search="$filters['search'] ?? ''" />
        </div>
    </div>
</section>
<section class="container-page trips-results" aria-labelledby="trips-results-heading">
    <form class="trips-filter-bar" method="GET" action="{{ route('trips.index') }}">
        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
        <label class="trips-filter-field">
            <span>{{ __('Destination') }}</span>
            <select name="destination"><option value="">{{ __('Every destination') }}</option>@foreach($destinations as $destination)<option value="{{ $destination->slug }}" @selected(($filters['destination'] ?? '') === $destination->slug)>{{ $destination->name }}</option>@endforeach</select>
        </label>
        <label class="trips-filter-field">
            <span>{{ __('Category') }}</span>
            <select name="category"><option value="">{{ __('All categories') }}</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>@endforeach</select>
        </label>
        <label class="trips-filter-field">
            <span>{{ __('Price per person') }}</span>
            <select name="maxPrice"><option value="">{{ __('Any price') }}</option>@foreach([20000,30000,50000] as $price)<option value="{{ $price }}" @selected((string) ($filters['maxPrice'] ?? '') === (string) $price)>{{ __('Under KES :price', ['price' => number_format($price)]) }}</option>@endforeach</select>
        </label>
        <label class="trips-filter-field">
            <span>{{ __('Guest rating') }}</span>
            <select name="minRating"><option value="">{{ __('Any rating') }}</option>@foreach(['4.5','4.8'] as $rating)<option value="{{ $rating }}" @selected((string) ($filters['minRating'] ?? '') === $rating)>{{ $rating }}+ {{ __('stars') }}</option>@endforeach</select>
        </label>
        <div class="trips-filter-final">
            <label class="trips-filter-field">
                <span>{{ __('Sort by') }}</span>
                <select name="sort"><option value="">{{ __('Recommended') }}</option><option value="price" @selected(($filters['sort'] ?? '') === 'price')>{{ __('Price: lowest') }}</option><option value="rating" @selected(($filters['sort'] ?? '') === 'rating')>{{ __('Rating: highest') }}</option></select>
            </label>
            <button type="submit">{{ __('Apply filters') }}<span aria-hidden="true">→</span></button>
        </div>
    </form>
    <div class="trips-results-heading">
        <div>
            <p class="trips-results-kicker">{{ __('Your next journey') }}</p>
            <h2 id="trips-results-heading">{{ $trips->total() }} {{ __('experiences') }}</h2>
        </div>
        @if(request()->query())
            <a class="trips-clear-link" href="{{ route('trips.index') }}">{{ __('Clear filters') }}<span aria-hidden="true">×</span></a>
        @endif
    </div>
    @if($trips->isEmpty())
        <div class="trips-empty-state">
            <span class="trips-empty-state__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m16 16 4 4"></path></svg></span>
            <h2>{{ __('No trips match those filters') }}</h2>
            <p>{{ __('Try another destination or clear a filter to see more experiences.') }}</p>
            <a href="{{ route('trips.index') }}">{{ __('View all trips') }}</a>
        </div>
    @else
        <div class="trips-results-grid">
            @foreach($trips as $trip)
                <x-trip-card :trip="$trip" />
            @endforeach
        </div>
        <div class="trips-pagination">{{ $trips->links() }}</div>
    @endif
</section>
@endsection
