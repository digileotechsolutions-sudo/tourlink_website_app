@extends('layouts.traveler')
@section('title', 'Traveler overview | TourLink')
@section('content')
<div class="mx-auto max-w-7xl space-y-6 p-4 sm:space-y-8 sm:p-8">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">Traveler overview</p><h1 class="mt-2 text-3xl font-black">Plan your next journey</h1><p class="mt-2 text-slate-600">Keep bookings, payments, favorites, and conversations together.</p></div>
        <a class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-ink px-5 text-sm font-bold text-white shadow-soft sm:w-auto" href="{{ route('trips.index') }}">Find a trip</a>
    </header>
    <div class="dashboard-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Your travel statistics">
        @foreach([['Bookings',$bookingCount],['Upcoming',$upcomingCount],['Favorites',$favoriteCount],['Pending payments',$pendingPaymentCount]] as [$label,$value])
            <article class="dashboard-kpi-card rounded-2xl border border-slate-200 bg-white p-5 shadow-soft"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-black text-ink">{{ $value }}</p></article>
        @endforeach
    </div>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft sm:p-5">
        <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-black">Recent bookings</h2><a class="inline-flex min-h-11 shrink-0 items-center rounded-lg px-3 text-sm font-bold text-ink hover:bg-slate-50" href="{{ route('traveler.bookings') }}">View all</a></div>
        <div class="mt-3 divide-y divide-slate-100">
            @forelse($recentBookings as $booking)
                <article class="flex flex-wrap items-start justify-between gap-3 py-4"><div class="min-w-0"><p class="break-words font-bold">{{ $booking->trip?->name ?? $booking->vehicle?->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $booking->reference }} · {{ $booking->start_date->format('d M Y') }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $booking->status->value }}</span></article>
            @empty
                <p class="py-8 text-sm text-slate-500">No bookings yet. <a class="font-bold text-ink underline underline-offset-2" href="{{ route('trips.index') }}">Find a trip</a>.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
