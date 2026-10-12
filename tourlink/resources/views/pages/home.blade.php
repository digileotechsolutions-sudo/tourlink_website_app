@extends('layouts.app')

@section('content')
@php
    $homeCategories = [
        ['name' => __('Tours & Adventures'), 'icon' => '⛰️'],
        ['name' => __('Road Trips'), 'icon' => '🚗'],
        ['name' => __('Bus Travel'), 'icon' => '🚌'],
        ['name' => __('Car Hire'), 'icon' => '🚙'],
        ['name' => __('Private Transfers'), 'icon' => '🚕'],
        ['name' => __('Group Travel'), 'icon' => '👥'],
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
    <div class="home-section-header">
        <p class="eyebrow mb-2">{{ __('Discover') }}</p>
        <h2>{{ __('Find your next kind of trip') }}</h2>
    </div>

    <div class="home-category-row" aria-label="{{ __('Travel categories') }}">
        @foreach($homeCategories as $category)
            <button type="button" class="home-category-card {{ $loop->first ? 'is-active' : '' }}">
                <span class="home-category-icon" aria-hidden="true">{{ $category['icon'] }}</span>
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

<section class="container-page home-section-block home-recommend-section">
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

<section id="destinations" class="container-page pt-24 sm:pt-32">
    <div class="mb-7 flex items-end justify-between gap-5"><div><p class="eyebrow mb-2">{{ __('Start somewhere beautiful') }}</p><h2 class="display text-3xl font-bold leading-tight text-ink sm:text-[42px]">{{ __('Where will you go next?') }}</h2><p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">{{ __('From big five mornings to slow coastal afternoons, find a trip that feels like yours.') }}</p></div><a class="hidden shrink-0 text-sm font-extrabold text-lagoon sm:block" href="{{ route('trips.index') }}">{{ __('View all trips') }} <span aria-hidden="true">›</span></a></div>
    @if($destinations->isEmpty())<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">{{ __('Destinations are being added. Check back soon.') }}</div>@else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($destinations as $destination)
                <a class="group relative h-56 overflow-hidden rounded-2xl bg-ink {{ $loop->first ? 'sm:col-span-2 sm:row-span-2 sm:h-full sm:min-h-[464px]' : '' }}" href="{{ route('trips.index', ['destination' => $destination->slug]) }}">
                    <img class="size-full object-cover transition duration-700 group-hover:scale-105" src="{{ $destination->image_url }}" alt="{{ $destination->name }}" loading="lazy"><span class="absolute inset-0 bg-ink/55"></span><span class="absolute bottom-4 left-4 text-white"><span class="block text-xl font-black">{{ $destination->name }}</span><span class="mt-1 block text-xs text-white/75">{{ $destination->trips_count }} {{ __('trips') }}</span></span>
                </a>
            @endforeach
        </div>
    @endif
</section>

<section class="container-page py-20 sm:py-24">
    <div class="mb-7 flex items-end justify-between gap-5"><div><p class="eyebrow mb-2">{{ __('Made for the moment') }}</p><h2 class="display text-3xl font-bold leading-tight text-ink sm:text-[42px]">{{ __('Trips worth the detour') }}</h2><p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">{{ __('Small groups, thoughtful itineraries, and hosts who know the way.') }}</p></div><a class="hidden shrink-0 text-sm font-extrabold text-lagoon sm:block" href="{{ route('trips.index') }}">{{ __('View all') }} <span aria-hidden="true">›</span></a></div>
    @if($featuredTrips->isEmpty())<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">{{ __('No published trips are available just yet.') }}</div>@else<div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@foreach($featuredTrips as $trip)<x-trip-card :trip="$trip" />@endforeach</div>@endif
</section>

<section class="bg-sand py-20 sm:py-24">
    <div class="container-page"><div class="mb-7 flex items-end justify-between gap-5"><div><p class="eyebrow mb-2">{{ __('The right ride changes everything') }}</p><h2 class="display text-3xl font-bold leading-tight text-ink sm:text-[42px]">{{ __('Move your way') }}</h2><p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">{{ __('Compare verified vehicles for game drives, road trips and everything in between.') }}</p></div><a class="hidden shrink-0 text-sm font-extrabold text-lagoon sm:block" href="{{ route('vehicles.index') }}">{{ __('Browse vehicles') }} <span aria-hidden="true">›</span></a></div>
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

@if($reviews->isNotEmpty())
<section class="bg-ink py-20 text-white sm:py-24"><div class="container-page"><div class="mb-10"><p class="eyebrow mb-3 text-sun">{{ __('Good words from the road') }}</p><h2 class="display text-4xl font-bold sm:text-5xl">{{ __('Stories they brought home.') }}</h2></div><div class="grid gap-5 md:grid-cols-3">@foreach($reviews as $review)<article class="rounded-2xl border border-white/10 bg-white/5 p-6"><div class="text-sun" aria-label="{{ $review->rating }} {{ __('out of 5 stars') }}">★★★★★</div><p class="mt-5 min-h-24 text-sm leading-7 text-white/75">“{{ $review->body }}”</p><div class="mt-5 border-t border-white/10 pt-4"><p class="text-xs font-extrabold">{{ $review->author?->name }}</p><p class="mt-1 text-[11px] text-white/45">{{ $review->trip?->name ?? $review->vehicle?->name }}</p></div></article>@endforeach</div></div></section>
@endif

@if($latestPosts->isNotEmpty())
<section class="container-page pb-20 sm:pb-24"><div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">{{ __('Travel journal') }}</p><h2 class="display mt-2 text-3xl font-bold leading-tight text-ink sm:text-[42px]">{{ __('Notes from the road') }}</h2></div><a class="text-xs font-extrabold text-lagoon" href="{{ route('blog.index') }}">{{ __('All articles') }} &rarr;</a></div><div class="mt-7 grid gap-5 md:grid-cols-3">@foreach($latestPosts as $post)<article class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">@if($post->image_url)<img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="h-40 w-full object-cover" loading="lazy">@endif<div class="flex flex-1 flex-col p-5"><p class="text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">{{ $post->created_at?->format('j M Y') }}</p><h3 class="mt-2 text-base font-extrabold leading-snug text-ink"><a class="hover:text-lagoon" href="{{ route('blog.show', ['blogPost' => $post->slug]) }}">{{ $post->title }}</a></h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $post->excerpt }}</p></div></article>@endforeach</div></section>
@endif
<section class="container-page py-16 sm:py-20"><div class="relative overflow-hidden rounded-[28px] bg-sun px-7 py-12 text-ink sm:px-14"><div class="relative z-10 max-w-xl"><p class="eyebrow text-ink/60">{{ __('Your local knowledge matters') }}</p><h2 class="display mt-3 text-4xl font-bold leading-tight sm:text-5xl">{{ __('Have a trip to share?') }}</h2><p class="mt-4 max-w-md text-sm leading-6 text-ink/70">{{ __('Join local operators and vehicle owners making travel across East Africa more accessible.') }}</p><div class="mt-7 flex flex-wrap gap-3">@if(Route::has('register'))<a class="rounded-full bg-ink px-5 py-3 text-sm font-extrabold text-white" href="{{ route('register', ['role' => 'OPERATOR']) }}">{{ __('Become an operator') }}</a><a class="rounded-full border border-ink/25 px-5 py-3 text-sm font-extrabold" href="{{ route('register', ['role' => 'VEHICLE_OWNER']) }}">{{ __('List your vehicle') }}</a>@else<a class="rounded-full bg-ink px-5 py-3 text-sm font-extrabold text-white" href="{{ route('home') }}">{{ __('Provider registration is coming soon') }}</a>@endif</div></div><span class="absolute -bottom-12 -right-4 text-[190px] leading-none text-ink/10" aria-hidden="true">✦</span></div></section>
@guest
    <x-sign-in-prompt />
@endguest
@endsection
<div>
    <!-- Nothing in life is to be feared, it is only to be understood. Now is the time to understand more, so that we may fear less. - Maria Skłodowska-Curie -->
</div>
