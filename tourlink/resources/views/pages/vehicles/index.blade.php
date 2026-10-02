@extends('layouts.app')

@section('title', 'Hire a vehicle | Havenedge Tourlink')
@section('content')
<section class="bg-lagoon py-12 text-white sm:py-16"><div class="container-page"><p class="eyebrow text-sun">Go your own way</p><h1 class="display mt-2 text-4xl font-bold sm:text-6xl">The right ride is here.</h1><div class="mt-8 max-w-4xl"><x-search-panel mode="vehicles" :search="$filters['search'] ?? ''" /></div></div></section>
<section class="container-page min-h-[55vh] py-10"><div class="mb-7 flex items-center justify-between"><p class="text-sm text-slate-500">{{ $vehicles->total() }} vehicles ready to go</p>@if(!empty($filters['search']))<a class="text-xs font-bold text-lagoon" href="{{ route('vehicles.index') }}">Clear search</a>@endif</div>
    @if($vehicles->isEmpty())<div class="grid place-items-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"><h2 class="font-extrabold text-ink">No vehicles match that search</h2><p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Try another location or vehicle type.</p><a class="mt-5 rounded-full bg-ink px-5 py-3 text-xs font-extrabold text-white" href="{{ route('vehicles.index') }}">Browse all vehicles</a></div>@else<div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@foreach($vehicles as $vehicle)<x-vehicle-card :vehicle="$vehicle" />@endforeach</div><div class="mt-9">{{ $vehicles->links() }}</div>@endif
</section>
@endsection
<div>
    <!-- When there is no desire, all things are at peace. - Laozi -->
</div>
