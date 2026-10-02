@extends('layouts.app')
@section('title', 'My bookings | Havenedge Tourlink')
@section('content')
<section class="bg-ink py-12 text-white"><div class="container-page"><p class="eyebrow text-sun">Your journeys</p><h1 class="display mt-2 text-4xl font-bold sm:text-6xl">My bookings</h1><p class="mt-4 max-w-xl text-sm text-white/70">Keep every request, payment and trip detail in one place.</p></div></section>
<section class="container-page py-10">
    @if(session('status'))<div class="mb-5 rounded-xl bg-leaf/10 p-4 text-sm font-bold text-leaf">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-5 rounded-xl bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
    <div class="grid gap-4">
        @forelse($bookings as $booking)
            <article class="rounded-2xl bg-white p-5 shadow-soft sm:flex sm:items-center sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $booking->reference }} · {{ $booking->status->value }}</p><h2 class="mt-2 text-lg font-black text-ink">{{ $booking->trip?->name ?? $booking->vehicle?->name ?? 'Journey' }}</h2><p class="mt-1 text-sm text-slate-500">{{ $booking->start_date->format('M j, Y') }} to {{ $booking->end_date->format('M j, Y') }} · KES {{ number_format($booking->total_amount) }}</p></div><span class="mt-4 inline-flex rounded-full bg-sand px-3 py-2 text-xs font-extrabold text-ink sm:mt-0">{{ $booking->payments->first()?->status?->value ?? 'PENDING' }}</span></article>
        @empty
            <div class="rounded-2xl bg-white p-10 text-center shadow-soft"><h2 class="text-xl font-black">No bookings yet</h2><p class="mt-2 text-sm text-slate-500">Find a trip or vehicle and start your next journey.</p></div>
        @endforelse
    </div>
    <div class="mt-7">{{ $bookings->links() }}</div>
</section>
@endsection
