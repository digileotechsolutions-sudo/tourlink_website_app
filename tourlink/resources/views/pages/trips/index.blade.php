@extends('layouts.app')

@section('title', 'Find a trip | TourLink')
@section('content')
<section class="bg-ink py-12 text-white sm:py-16"><div class="container-page"><p class="eyebrow text-sun">Find your next story</p><h1 class="display mt-2 text-4xl font-bold sm:text-6xl">Trips, made memorable.</h1><div class="mt-8 max-w-4xl"><x-search-panel mode="trips" :search="$filters['search'] ?? ''" /></div></div></section>
<section class="container-page min-h-[55vh] py-10">
    <form class="mb-7 grid gap-3 rounded-2xl bg-white p-4 shadow-soft sm:grid-cols-2 lg:grid-cols-5" method="GET" action="{{ route('trips.index') }}">
        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
        <select class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs font-bold" name="destination"><option value="">Every destination</option>@foreach($destinations as $destination)<option value="{{ $destination->slug }}" @selected(($filters['destination'] ?? '') === $destination->slug)>{{ $destination->name }}</option>@endforeach</select>
        <select class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs font-bold" name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>@endforeach</select>
        <select class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs font-bold" name="maxPrice"><option value="">Any price</option>@foreach([20000,30000,50000] as $price)<option value="{{ $price }}" @selected((string) ($filters['maxPrice'] ?? '') === (string) $price)>Under KES {{ number_format($price) }}</option>@endforeach</select>
        <select class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs font-bold" name="minRating"><option value="">Any rating</option>@foreach(['4.5','4.8'] as $rating)<option value="{{ $rating }}" @selected((string) ($filters['minRating'] ?? '') === $rating)>{{ $rating }}+ stars</option>@endforeach</select>
        <div class="flex gap-2"><select class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs font-bold" name="sort"><option value="">Recommended</option><option value="price" @selected(($filters['sort'] ?? '') === 'price')>Price: lowest</option><option value="rating" @selected(($filters['sort'] ?? '') === 'rating')>Rating: highest</option></select><button class="rounded-xl bg-ink px-4 py-3 text-xs font-extrabold text-white hover:bg-lagoon" type="submit">Apply</button></div>
    </form>
    <div class="mb-6 flex items-center justify-between"><p class="text-sm text-slate-500">{{ $trips->total() }} experiences</p>@if(request()->query())<a class="text-xs font-bold text-lagoon" href="{{ route('trips.index') }}">Clear filters</a>@endif</div>
    @if($trips->isEmpty())<div class="grid place-items-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"><h2 class="font-extrabold text-ink">No trips match those filters</h2><p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Try another destination or clear a filter to see more experiences.</p><a class="mt-5 rounded-full bg-ink px-5 py-3 text-xs font-extrabold text-white" href="{{ route('trips.index') }}">View all trips</a></div>@else<div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@foreach($trips as $trip)<x-trip-card :trip="$trip" />@endforeach</div><div class="mt-9">{{ $trips->links() }}</div>@endif
</section>
@endsection
<div>
    <!-- Breathing in, I calm body and mind. Breathing out, I smile. - Thich Nhat Hanh -->
</div>
