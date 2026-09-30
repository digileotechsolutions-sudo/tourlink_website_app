@extends('layouts.app')

@section('content')
<section class="grain relative overflow-hidden bg-hero-glow text-white">
    <div class="container-page relative grid min-h-[650px] items-center gap-12 py-20 lg:grid-cols-[1.02fr_.98fr] lg:py-24">
        <div class="relative z-10">
            <span class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-2 text-[11px] font-bold uppercase tracking-[.16em] text-white/80"><span class="text-sun" aria-hidden="true">✦</span> Kenya's travel marketplace</span>
            <h1 class="display max-w-2xl text-6xl font-bold leading-[.94] sm:text-7xl lg:text-[88px]">Go further.<br><span class="text-sun">Feel more.</span></h1>
            <p class="mt-7 max-w-lg text-base leading-7 text-white/75 sm:text-lg">Discover trips, hire verified vehicles, and connect with trusted tour operators. Your next story starts here.</p>
            <div class="mt-9 flex flex-wrap gap-3"><a class="rounded-full bg-sun px-6 py-3.5 text-sm font-extrabold text-ink hover:bg-orange-300" href="{{ route('trips.index') }}">Explore trips <span aria-hidden="true">↗</span></a><a class="rounded-full border border-white/25 px-6 py-3.5 text-sm font-bold text-white hover:bg-white/10" href="#how-it-works">How it works</a></div>
            <div class="mt-10 flex flex-wrap items-center gap-4 text-xs text-white/65"><span><strong class="text-white">{{ number_format($journeyCount) }}</strong> journeys booked</span><span class="size-1 rounded-full bg-sun"></span><span><strong class="text-white">{{ number_format($providerCount) }}</strong> active providers</span></div>
        </div>
        <div class="relative hidden h-[460px] lg:block">
            <div class="absolute right-0 top-1/2 h-[420px] w-[370px] -translate-y-1/2 rotate-3 overflow-hidden rounded-[32px] border-8 border-white/15 shadow-2xl"><img class="size-full object-cover" src="https://images.unsplash.com/photo-1516426122078-c23e76319801?auto=format&fit=crop&w=1000&q=90" alt="Elephant in a Kenyan landscape" fetchpriority="high"></div>
            @if($featuredTrips->isNotEmpty())<div class="absolute bottom-8 left-2 z-10 max-w-[260px] rounded-2xl bg-white p-4 text-ink shadow-xl"><p class="text-[10px] font-extrabold uppercase tracking-wider text-lagoon">Featured journey</p><p class="mt-1 font-extrabold">{{ $featuredTrips->first()->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $featuredTrips->first()->destination?->name }} · {{ $featuredTrips->first()->duration_days }} days</p></div>@endif
        </div>
        <div class="absolute bottom-6 right-6 hidden text-right text-[10px] font-bold uppercase tracking-[.16em] text-white/40 lg:block">Made for the moment<br>Kenya · East Africa</div>
    </div>
</section>

<section id="destinations" class="container-page pt-24 sm:pt-32">
    <div class="mb-7 flex items-end justify-between gap-5"><div><p class="eyebrow mb-2">Start somewhere beautiful</p><h2 class="display text-3xl font-bold leading-tight text-ink sm:text-[42px]">Where will you go next?</h2><p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">From big five mornings to slow coastal afternoons, find a trip that feels like yours.</p></div><a class="hidden shrink-0 text-sm font-extrabold text-lagoon sm:block" href="{{ route('trips.index') }}">View all trips <span aria-hidden="true">›</span></a></div>
    @if($destinations->isEmpty())<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Destinations are being added. Check back soon.</div>@else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($destinations as $destination)
                <a class="group relative h-56 overflow-hidden rounded-2xl bg-ink {{ $loop->first ? 'sm:col-span-2 sm:row-span-2 sm:h-full sm:min-h-[464px]' : '' }}" href="{{ route('trips.index', ['destination' => $destination->slug]) }}">
                    <img class="size-full object-cover transition duration-700 group-hover:scale-105" src="{{ $destination->image_url }}" alt="{{ $destination->name }}" loading="lazy"><span class="absolute inset-0 bg-ink/55"></span><span class="absolute bottom-4 left-4 text-white"><span class="block text-xl font-black">{{ $destination->name }}</span><span class="mt-1 block text-xs text-white/75">{{ $destination->trips_count }} trips</span></span>
                </a>
            @endforeach
        </div>
    @endif
</section>

<section class="container-page py-20 sm:py-24">
    <div class="mb-7 flex items-end justify-between gap-5"><div><p class="eyebrow mb-2">Made for the moment</p><h2 class="display text-3xl font-bold leading-tight text-ink sm:text-[42px]">Trips worth the detour</h2><p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">Small groups, thoughtful itineraries, and hosts who know the way.</p></div><a class="hidden shrink-0 text-sm font-extrabold text-lagoon sm:block" href="{{ route('trips.index') }}">View all <span aria-hidden="true">›</span></a></div>
    @if($featuredTrips->isEmpty())<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">No published trips are available just yet.</div>@else<div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@foreach($featuredTrips as $trip)<x-trip-card :trip="$trip" />@endforeach</div>@endif
</section>

<section class="bg-sand py-20 sm:py-24">
    <div class="container-page"><div class="mb-7 flex items-end justify-between gap-5"><div><p class="eyebrow mb-2">The right ride changes everything</p><h2 class="display text-3xl font-bold leading-tight text-ink sm:text-[42px]">Move your way</h2><p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">Compare verified vehicles for game drives, road trips and everything in between.</p></div><a class="hidden shrink-0 text-sm font-extrabold text-lagoon sm:block" href="{{ route('vehicles.index') }}">Browse vehicles <span aria-hidden="true">›</span></a></div>
        @if($vehicles->isEmpty())<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">No verified vehicles are available just yet.</div>@else<div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@foreach($vehicles as $vehicle)<x-vehicle-card :vehicle="$vehicle" />@endforeach</div>@endif
    </div>
</section>

<section id="how-it-works" class="container-page grid items-center gap-12 py-20 sm:py-24 lg:grid-cols-[.9fr_1.1fr]">
    <div><p class="eyebrow mb-3">Travel, with more trust</p><h2 class="display text-4xl font-bold leading-tight sm:text-5xl">Every good journey starts with a little confidence.</h2><p class="mt-5 max-w-md text-sm leading-7 text-slate-500">Find trusted local providers, compare clear details and keep your journey in one place.</p><div class="mt-8 grid gap-5 sm:grid-cols-2"><div class="flex gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-lagoon/10 text-lagoon" aria-hidden="true">✓</span><div><h3 class="text-sm font-extrabold">Verified people</h3><p class="mt-1 text-xs leading-5 text-slate-500">Visible badges and honest reviews.</p></div></div><div class="flex gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sun/20 text-ink" aria-hidden="true">♡</span><div><h3 class="text-sm font-extrabold">Human support</h3><p class="mt-1 text-xs leading-5 text-slate-500">Message providers about the details.</p></div></div></div></div>
    <div class="relative min-h-[390px] overflow-hidden rounded-[28px] bg-ink"><img class="absolute inset-0 size-full object-cover opacity-85" src="https://images.unsplash.com/photo-1535338454770-8be927b5a00b?auto=format&fit=crop&w=1200&q=85" alt="Kenyan safari landscape" loading="lazy"><div class="absolute inset-0 bg-ink/45"></div><p class="display absolute bottom-7 left-7 text-3xl font-bold text-white">One platform.<br><span class="text-sun">Endless journeys.</span></p></div>
</section>

@if($reviews->isNotEmpty())
<section class="bg-ink py-20 text-white sm:py-24"><div class="container-page"><div class="mb-10"><p class="eyebrow mb-3 text-sun">Good words from the road</p><h2 class="display text-4xl font-bold sm:text-5xl">Stories they brought home.</h2></div><div class="grid gap-5 md:grid-cols-3">@foreach($reviews as $review)<article class="rounded-2xl border border-white/10 bg-white/5 p-6"><div class="text-sun" aria-label="{{ $review->rating }} out of 5 stars">★★★★★</div><p class="mt-5 min-h-24 text-sm leading-7 text-white/75">“{{ $review->body }}”</p><div class="mt-5 border-t border-white/10 pt-4"><p class="text-xs font-extrabold">{{ $review->author?->name }}</p><p class="mt-1 text-[11px] text-white/45">{{ $review->trip?->name ?? $review->vehicle?->name }}</p></div></article>@endforeach</div></div></section>
@endif

@if($latestPosts->isNotEmpty())
<section class="container-page pb-20 sm:pb-24"><div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">Travel journal</p><h2 class="display mt-2 text-3xl font-bold leading-tight text-ink sm:text-[42px]">Notes from the road</h2></div><a class="text-xs font-extrabold text-lagoon" href="{{ route('blog.index') }}">All articles &rarr;</a></div><div class="mt-7 grid gap-5 md:grid-cols-3">@foreach($latestPosts as $post)<article class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">@if($post->image_url)<img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="h-40 w-full object-cover" loading="lazy">@endif<div class="flex flex-1 flex-col p-5"><p class="text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">{{ $post->created_at?->format('j M Y') }}</p><h3 class="mt-2 text-base font-extrabold leading-snug text-ink"><a class="hover:text-lagoon" href="{{ route('blog.show', $post) }}">{{ $post->title }}</a></h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $post->excerpt }}</p></div></article>@endforeach</div></section>
@endif
<section class="container-page py-16 sm:py-20"><div class="relative overflow-hidden rounded-[28px] bg-sun px-7 py-12 text-ink sm:px-14"><div class="relative z-10 max-w-xl"><p class="eyebrow text-ink/60">Your local knowledge matters</p><h2 class="display mt-3 text-4xl font-bold leading-tight sm:text-5xl">Have a trip to share?</h2><p class="mt-4 max-w-md text-sm leading-6 text-ink/70">Join local operators and vehicle owners making travel across East Africa more accessible.</p><div class="mt-7 flex flex-wrap gap-3">@if(Route::has('register'))<a class="rounded-full bg-ink px-5 py-3 text-sm font-extrabold text-white" href="{{ route('register', ['role' => 'OPERATOR']) }}">Become an operator</a><a class="rounded-full border border-ink/25 px-5 py-3 text-sm font-extrabold" href="{{ route('register', ['role' => 'VEHICLE_OWNER']) }}">List your vehicle</a>@else<a class="rounded-full bg-ink px-5 py-3 text-sm font-extrabold text-white" href="{{ route('home') }}">Provider registration is coming soon</a>@endif</div></div><span class="absolute -bottom-12 -right-4 text-[190px] leading-none text-ink/10" aria-hidden="true">✦</span></div></section>
@endsection
<div>
    <!-- Nothing in life is to be feared, it is only to be understood. Now is the time to understand more, so that we may fear less. - Maria Skłodowska-Curie -->
</div>
