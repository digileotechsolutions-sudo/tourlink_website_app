@extends('layouts.operator')
@section('title', __('Operator overview').' | Havenedge Tourlink')
@section('content')
<div class="mx-auto max-w-7xl space-y-8 p-4 sm:p-8">
    <div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">{{ __('Operator overview') }}</p><h1 class="mt-2 text-3xl font-black text-slate-950">{{ __('Run your travel business') }}</h1><p class="mt-2 text-slate-600">{{ __('Manage trips, travelers, bookings, and company verification from one workspace.') }}</p></div>
    <section class="grid gap-4 rounded border border-[#1F0052] bg-[#FFD51E] p-5" aria-labelledby="verification-reminder-heading">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 id="verification-reminder-heading" class="text-lg font-black text-[#1F0052]">{{ __('Company profile: :percentage% complete', ['percentage' => $verificationCompletionPercentage]) }}</h2>
                <p class="mt-1 text-sm text-[#1F0052]">{{ $verificationCompletionPercentage < 100 ? __('Your account starts at 50%. Complete your company details and upload each required business document.') : __('Your account, company details, and required business documents are complete.') }}</p>
            </div>
            @if ($verificationCompletionPercentage < 100)
                <a class="inline-flex min-h-11 items-center rounded bg-[#1F0052] px-5 py-3 text-sm font-bold text-white hover:bg-[#150039]" href="{{ route('operator.profile') }}">{{ __('Complete profile') }}</a>
            @endif
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-[#1F0052]/20" role="progressbar" aria-label="Company profile completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $verificationCompletionPercentage }}">
            <div class="h-full rounded-full bg-[#FF467A]" style="width: {{ $verificationCompletionPercentage }}%"></div>
        </div>
    </section>
    <div class="dashboard-kpi-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-6">@foreach([['Trips', $tripCount], ['Published', $publishedTripCount], ['Bookings', $bookingCount], ['Pending', $pendingBookingCount], ['Customers', $customerCount], ['Earnings', 'KES '.number_format($earnings)]] as [$label, $value])<div class="dashboard-kpi-card rounded-2xl border border-slate-200 bg-white p-5 shadow-soft"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __($label) }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p></div>@endforeach</div>
    <div class="grid gap-5 xl:grid-cols-[1.35fr_1fr]"><section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft sm:p-5"><div class="flex items-center justify-between gap-3"><h2 class="text-lg font-black">{{ __('Recent bookings') }}</h2><a class="inline-flex min-h-11 shrink-0 items-center rounded-lg px-3 text-sm font-bold text-ink hover:bg-slate-50" href="{{ route('operator.bookings.index') }}">{{ __('View all') }}</a></div><div class="mt-3 divide-y divide-slate-100">@forelse($recentBookings as $booking)<article class="flex flex-wrap items-center justify-between gap-3 py-4"><div class="min-w-0"><p class="break-words font-bold">{{ $booking->traveler->name }}</p><p class="text-sm text-slate-500">{{ $booking->trip->name }} · {{ $booking->reference }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ __($booking->status->value) }}</span></article>@empty<p class="py-8 text-sm text-slate-500">{{ __('No bookings yet.') }}</p>@endforelse</div></section><section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft sm:p-5"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-black">{{ __('Your trips') }}</h2><a class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-ink px-4 text-sm font-bold text-white sm:w-auto" href="{{ route('operator.trips.create') }}">{{ __('Create trip') }}</a></div><div class="mt-3 grid gap-3">@forelse($trips as $trip)<a href="{{ route('operator.trips.edit', $trip) }}" class="rounded-xl bg-slate-50 p-4 shadow-sm hover:bg-emerald-50"><div class="flex justify-between gap-3"><strong class="min-w-0 break-words">{{ $trip->name }}</strong><span class="shrink-0 text-xs font-bold">{{ __(':count bookings', ['count' => $trip->bookings_count]) }}</span></div><p class="mt-1 text-xs text-slate-500">{{ __($trip->status->value) }} · {{ __($trip->verification_status->value) }}</p></a>@empty<p class="py-8 text-sm text-slate-500">{{ __('Create your first trip.') }}</p>@endforelse</div></section></div>
</div>
@endsection
