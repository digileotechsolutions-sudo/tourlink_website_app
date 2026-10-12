@extends('layouts.app')

@section('content')
@php
    $homeCategories = [
        ['name' => __('Tours & Adventures'), 'icon' => 'mountain'],
        ['name' => __('Road Trips'), 'icon' => 'route'],
        ['name' => __('Bus Travel'), 'icon' => 'bus'],
        ['name' => __('Car Hire'), 'icon' => 'car'],
        ['name' => __('Private Transfers'), 'icon' => 'transfer'],
        ['name' => __('Group Travel'), 'icon' => 'group'],
    ];
@endphp

<section class="home-native-shell">
    <div class="container-page">
        <div class="home-native-hero">
            <div class="home-native-copy">
                <p class="home-native-eyebrow">{{ __('Travel with confidence') }}</p>
                <h1>{{ __('Where will your next journey take you?') }}</h1>
                <p>{{ __('Discover handpicked trips, trusted operators, and rides that fit your pace — all in one beautiful mobile-first app experience.') }}</p>
            </div>

            <form class="home-native-search" method="GET" action="{{ route('trips.index') }}">
                <div class="home-search-row">
                    <label>
                        <span>{{ __('Destination') }}</span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Kenya, Zanzibar, Maasai Mara...') }}" aria-label="{{ __('Destination') }}">
                    </label>
                    <button type="submit" aria-label="{{ __('Search trips') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="6"></circle><path d="m16 16 4 4"></path></svg>
                    </button>
                </div>
                <div class="home-search-grid">
                    <label>
                        <span>{{ __('From') }}</span>
                        <input type="text" name="from" value="{{ request('from') }}" placeholder="{{ __('Nairobi') }}" aria-label="{{ __('Departure location') }}">
                    </label>
                    <label>
                        <span>{{ __('When') }}</span>
                        <input type="date" name="date" value="{{ request('date') }}" aria-label="{{ __('Travel date') }}">
                    </label>
                    <label>
                        <span>{{ __('Guests') }}</span>
                        <select name="guests" aria-label="{{ __('Passengers') }}">
                            <option value="2" @selected(request('guests') == '2')>2 Guests</option>
                            <option value="4" @selected(request('guests') == '4')>4 Guests</option>
                            <option value="6" @selected(request('guests') == '6')>6 Guests</option>
                        </select>
                    </label>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="container-page home-categories-wrap">
    <div class="home-section-header home-category-heading">
        <div>
            <p class="eyebrow mb-2">{{ __('Discover') }} <span class="home-category-heading-mark" aria-hidden="true"></span></p>
            <h2>{{ __('Find your next kind of trip') }}</h2>
            <p class="home-category-heading-note">{{ __('Choose a travel style to get started') }}</p>
        </div>
    </div>

    <div class="home-category-row" aria-label="{{ __('Travel categories') }}">
        @foreach($homeCategories as $category)
            <button type="button" class="home-category-card {{ $loop->first ? 'is-active' : '' }}">
                <span class="home-category-icon" aria-hidden="true">
                    @switch($category['icon'])
                        @case('mountain')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 19 6.5-11 4 6 2.5-3 5 8H3Z"></path><path d="m8 10 1.5 2 1.5-2"></path></svg>
                            @break
                        @case('route')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="18" r="2.5"></circle><circle cx="18" cy="6" r="2.5"></circle><path d="M8.5 18H11a3 3 0 0 0 3-3v-2a3 3 0 0 1 3-3h1"></path></svg>
                            @break
                        @case('bus')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17V6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5V17"></path><path d="M5 11h14M7 17v2m10-2v2M8 14h.01M16 14h.01"></path><path d="M7 19h10"></path></svg>
                            @break
                        @case('car')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m5 11 1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11l2 2v5h-2v-2H5v2H3v-5l2-2Z"></path><path d="M5 11h14M7 13h.01M17 13h.01"></path></svg>
                            @break
                        @case('transfer')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h14m0 0-3-3m3 3-3 3M20 16H6m0 0 3-3m-3 3 3 3"></path></svg>
                            @break
                        @case('group')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"></circle><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.5a3 3 0 0 1 0 5.8M17 14a5 5 0 0 1 3.5 5"></path></svg>
                            @break
                    @endswitch
                </span>
                <span>{{ $category['name'] }}</span>
            </button>
        @endforeach
    </div>
</section>

<section class="container-page home-section-block">
    <div class="home-section-header">
        <div>
            <p class="eyebrow mb-2">{{ __('Featured') }}</p>
            <h2>{{ __('Trips worth planning') }}</h2>
        </div>
        <a href="{{ route('trips.index') }}" class="home-view-all">{{ __('View all') }} <span aria-hidden="true">→</span></a>
    </div>

    @if($featuredTrips->isEmpty())
        <div class="home-empty-state">{{ __('No published trips are available just yet.') }}</div>
    @else
        <div class="home-trip-grid">
            @foreach($featuredTrips as $trip)
                <x-trip-card :trip="$trip" />
            @endforeach
        </div>
    @endif
</section>

<section id="destinations" class="container-page home-section-block home-recommend-section">
    <div class="home-section-header">
        <div>
            <p class="eyebrow mb-2">{{ __('Recommended') }}</p>
            <h2>{{ __('Discover your next adventure') }}</h2>
        </div>
        <a href="{{ route('trips.index') }}" class="home-view-all">{{ __('Explore more') }} <span aria-hidden="true">→</span></a>
    </div>

    @if($destinations->isEmpty())
        <div class="home-empty-state">{{ __('Destinations are being added. Check back soon.') }}</div>
    @else
        <div class="home-destination-grid">
            @foreach($destinations as $destination)
                <a href="{{ route('trips.index', ['destination' => $destination->slug]) }}" class="home-destination-card">
                    <img src="{{ $destination->image_url }}" alt="{{ $destination->name }}" loading="lazy" decoding="async">
                    <div class="home-destination-overlay"></div>
                    <div class="home-destination-copy">
                        <span class="home-destination-badge">{{ $destination->trips_count }} {{ __('trips') }}</span>
                        <h3>{{ $destination->name }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>

<section class="home-cta-wrap">
    <div class="container-page">
        <div class="home-cta-card">
            <div>
                <p class="eyebrow">{{ __('Travel smarter') }}</p>
                <h2>{{ __('Your next unforgettable journey starts here.') }}</h2>
            </div>
            <div class="home-cta-actions">
                <a href="{{ route('trips.index') }}" class="home-primary-button">{{ __('Explore trips') }}</a>
                <a href="{{ route('vehicles.index') }}" class="home-secondary-button">{{ __('Browse vehicles') }}</a>
            </div>
        </div>
    </div>
</section>

@if($reviews->isNotEmpty())
<section class="home-review-wrap">
    <div class="container-page">
        <div class="home-section-header">
            <div>
                <p class="eyebrow mb-2">{{ __('Good words') }}</p>
                <h2>{{ __('Stories they brought home') }}</h2>
            </div>
        </div>
        <div class="home-review-grid">
            @foreach($reviews as $review)
                <article class="home-review-card">
                    <div class="home-review-stars" aria-label="{{ $review->rating }} {{ __('out of 5 stars') }}">★★★★★</div>
                    <p>“{{ $review->body }}”</p>
                    <div class="home-review-meta">
                        <strong>{{ $review->author?->name }}</strong>
                        <span>{{ $review->trip?->name ?? $review->vehicle?->name }}</span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($latestPosts->isNotEmpty())
<section class="container-page home-section-block">
    <div class="home-section-header">
        <div>
            <p class="eyebrow mb-2">{{ __('Inspiration') }}</p>
            <h2>{{ __('Notes from the road') }}</h2>
        </div>
        <a href="{{ route('blog.index') }}" class="home-view-all">{{ __('All articles') }} <span aria-hidden="true">→</span></a>
    </div>
    <div class="home-post-grid">
        @foreach($latestPosts as $post)
            <article class="home-post-card">
                @if($post->image_url)
                    <img src="{{ $post->image_url }}" alt="{{ $post->title }}" loading="lazy" decoding="async">
                @endif
                <div class="home-post-body">
                    <p class="home-post-date">{{ $post->created_at?->format('j M Y') }}</p>
                    <h3><a href="{{ route('blog.show', ['blogPost' => $post->slug]) }}">{{ $post->title }}</a></h3>
                    <p>{{ $post->excerpt }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif

@guest
    <x-sign-in-prompt />
@endguest

<section class="container-page home-section-block">
    <div><div class="mb-7 flex items-end justify-between gap-5"><div><p class="eyebrow mb-2">{{ __('The right ride changes everything') }}</p><h2 class="display text-3xl font-bold leading-tight text-ink sm:text-[42px]">{{ __('Move your way') }}</h2><p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">{{ __('Compare verified vehicles for game drives, road trips and everything in between.') }}</p></div><a class="hidden shrink-0 text-sm font-extrabold text-lagoon sm:block" href="{{ route('vehicles.index') }}">{{ __('Browse vehicles') }} <span aria-hidden="true">›</span></a></div>
        @if($vehicles->isEmpty())<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">{{ __('No verified vehicles are available just yet.') }}</div>@else<div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@foreach($vehicles as $vehicle)<x-vehicle-card :vehicle="$vehicle" />@endforeach</div>@endif
    </div>
</section>

<section id="how-it-works" class="container-page grid items-center gap-12 py-20 sm:py-24 lg:grid-cols-[.9fr_1.1fr]">
    <div><p class="eyebrow mb-3">{{ __('Travel, with more trust') }}</p><h2 class="display text-4xl font-bold leading-tight sm:text-5xl">{{ __('Every good journey starts with a little confidence.') }}</h2><p class="mt-5 max-w-md text-sm leading-7 text-slate-500">{{ __('Find trusted local providers, compare clear details and keep your journey in one place.') }}</p><div class="mt-8 grid gap-5 sm:grid-cols-2"><div class="flex gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-lagoon/10 text-lagoon" aria-hidden="true">✓</span><div><h3 class="text-sm font-extrabold">{{ __('Verified people') }}</h3><p class="mt-1 text-xs leading-5 text-slate-500">{{ __('Visible badges and honest reviews.') }}</p></div></div><div class="flex gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sun/20 text-ink" aria-hidden="true">♡</span><div><h3 class="text-sm font-extrabold">{{ __('Human support') }}</h3><p class="mt-1 text-xs leading-5 text-slate-500">{{ __('Message providers about the details.') }}</p></div></div></div></div>
     <div class="relative min-h-[390px] overflow-hidden rounded-[28px] bg-ink"><img class="absolute inset-0 size-full object-cover opacity-85" src="https://images.unsplash.com/photo-1535338454770-8be927b5a00b?auto=format&fit=crop&w=720&q=75" srcset="https://images.unsplash.com/photo-1535338454770-8be927b5a00b?auto=format&fit=crop&w=480&q=70 480w, https://images.unsplash.com/photo-1535338454770-8be927b5a00b?auto=format&fit=crop&w=720&q=75 720w, https://images.unsplash.com/photo-1535338454770-8be927b5a00b?auto=format&fit=crop&w=1100&q=80 1100w" sizes="(max-width: 1023px) 100vw, 560px" alt="{{ __('Kenyan safari landscape') }}" loading="lazy" decoding="async"><div class="absolute inset-0 bg-ink/45"></div><p class="display absolute bottom-7 left-7 text-3xl font-bold text-white">{{ __('One platform.') }}<br><span class="text-sun">{{ __('Endless journeys.') }}</span></p></div>
</section>

<section id="about-us" class="bg-white py-20 sm:py-24" aria-labelledby="about-us-heading">
    <div class="container-page grid gap-10 md:grid-cols-[.8fr_1.2fr] md:gap-16">
        <div>
            <p class="eyebrow mb-3">{{ __('About Havenedge Tourlink') }}</p>
            <h2 id="about-us-heading" class="display max-w-md text-4xl font-bold leading-tight text-ink sm:text-5xl">{{ __('A more connected way to explore.') }}</h2>
        </div>
        <div class="max-w-2xl">
            <p class="text-lg leading-8 text-slate-700">{{ __('Havenedge Tourlink brings travelers together with local tour operators and vehicle owners in one place.') }}</p>
            <p class="mt-4 text-sm leading-7 text-slate-500">{{ __('Explore trips, compare vehicles, connect with providers, and keep your bookings organized from your account. Whether you are planning your next journey or sharing local knowledge, start here.') }}</p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a class="inline-flex min-h-11 items-center rounded-full bg-ink px-5 text-sm font-extrabold text-white hover:bg-[#150039]" href="{{ route('trips.index') }}">{{ __('Explore trips') }}</a>
                <a class="inline-flex min-h-11 items-center rounded-full border border-slate-300 px-5 text-sm font-bold text-ink hover:bg-slate-50" href="{{ route('vehicles.index') }}">{{ __('Browse vehicles') }}</a>
                <a class="inline-flex min-h-11 items-center rounded-full border border-slate-300 px-5 text-sm font-bold text-ink hover:bg-slate-50" href="{{ route('contact') }}">{{ __('Talk to us') }}</a>
                <a class="inline-flex min-h-11 items-center rounded-full border border-slate-300 px-5 text-sm font-bold text-ink hover:bg-slate-50" href="{{ route('about') }}">{{ __('About Havenedge Tourlink') }}</a>
            </div>
        </div>
    </div>
</section>

<section class="container-page py-16 sm:py-20"><div class="relative overflow-hidden rounded-[28px] bg-sun px-7 py-12 text-ink sm:px-14"><div class="relative z-10 max-w-xl"><p class="eyebrow text-ink/60">{{ __('Your local knowledge matters') }}</p><h2 class="display mt-3 text-4xl font-bold leading-tight sm:text-5xl">{{ __('Have a trip to share?') }}</h2><p class="mt-4 max-w-md text-sm leading-6 text-ink/70">{{ __('Join local operators and vehicle owners making travel across East Africa more accessible.') }}</p><div class="mt-7 flex flex-wrap gap-3">@if(Route::has('register'))<a class="rounded-full bg-ink px-5 py-3 text-sm font-extrabold text-white" href="{{ route('register', ['role' => 'OPERATOR']) }}">{{ __('Become an operator') }}</a><a class="rounded-full border border-ink/25 px-5 py-3 text-sm font-extrabold" href="{{ route('register', ['role' => 'VEHICLE_OWNER']) }}">{{ __('List your vehicle') }}</a>@else<a class="rounded-full bg-ink px-5 py-3 text-sm font-extrabold text-white" href="{{ route('home') }}">{{ __('Provider registration is coming soon') }}</a>@endif</div></div><span class="absolute -bottom-12 -right-4 text-[190px] leading-none text-ink/10" aria-hidden="true">✦</span></div></section>
@endsection
