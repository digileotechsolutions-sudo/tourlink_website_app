@extends('layouts.traveler')
@section('title', __('Traveler overview').' | Havenedge Tourlink')
@section('content')
<div class="mx-auto max-w-7xl space-y-6 p-4 sm:space-y-8 sm:p-8">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">{{ __('Traveler overview') }}</p><h1 class="mt-2 text-3xl font-black">{{ __('Plan your next journey') }}</h1><p class="mt-2 text-slate-600">{{ __('Keep bookings, payments, favorites, and conversations together.') }}</p></div>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <a class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 text-sm font-bold text-ink shadow-soft" href="{{ route('support.index') }}">{{ __('Chat with Support') }}</a>
            <a class="inline-flex min-h-12 items-center justify-center rounded-xl bg-ink px-5 text-sm font-bold text-white shadow-soft" href="{{ route('trips.index') }}">{{ __('Find a trip') }}</a>
        </div>
    </header>
    <div class="dashboard-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('Your travel statistics') }}">
        @foreach([['Bookings',$bookingCount],['Upcoming',$upcomingCount],['Favorites',$favoriteCount],['Pending payments',$pendingPaymentCount]] as [$label,$value])
            <article class="dashboard-kpi-card rounded-2xl border border-slate-200 bg-white p-5 shadow-soft"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __($label) }}</p><p class="mt-2 text-2xl font-black text-ink">{{ $value }}</p></article>
        @endforeach
    </div>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft sm:p-5">
        <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-black">{{ __('Recent bookings') }}</h2><a class="inline-flex min-h-11 shrink-0 items-center rounded-lg px-3 text-sm font-bold text-ink hover:bg-slate-50" href="{{ route('traveler.bookings') }}">{{ __('View all') }}</a></div>
        <div class="mt-3 divide-y divide-slate-100">
            @forelse($recentBookings as $booking)
                <article class="flex flex-wrap items-start justify-between gap-3 py-4"><div class="min-w-0"><p class="break-words font-bold">{{ $booking->trip?->name ?? $booking->vehicle?->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $booking->reference }} · {{ $booking->start_date->locale(app()->getLocale())->translatedFormat('d M Y') }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ __($booking->status->value) }}</span></article>
            @empty
                <p class="py-8 text-sm text-slate-500">{{ __('No bookings yet.') }} <a class="font-bold text-ink underline underline-offset-2" href="{{ route('trips.index') }}">{{ __('Find a trip') }}</a>.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
