@extends('layouts.operator')
@section('title', 'Operator overview | TourLink')
@section('content')
<div class="mx-auto max-w-7xl space-y-8 p-4 sm:p-8">
    <div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">Operator overview</p><h1 class="mt-2 text-3xl font-black text-slate-950">Run your travel business</h1><p class="mt-2 text-slate-600">Manage trips, travelers, bookings, and company verification from one workspace.</p></div>
    <section class="grid gap-4 rounded border border-amber-200 bg-amber-50 p-5" aria-labelledby="verification-reminder-heading">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 id="verification-reminder-heading" class="text-lg font-black text-slate-950">Company profile: {{ $verificationCompletionPercentage }}% complete</h2>
                <p class="mt-1 text-sm text-amber-950">{{ $verificationCompletionPercentage < 100 ? 'Your account starts at 50%. Complete your company details and upload each required business document.' : 'Your account, company details, and required business documents are complete.' }}</p>
            </div>
            @if ($verificationCompletionPercentage < 100)
                <a class="inline-flex min-h-11 items-center rounded bg-amber-800 px-5 py-3 text-sm font-bold text-white hover:bg-amber-900" href="{{ route('operator.profile') }}">Complete profile</a>
            @endif
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-white" role="progressbar" aria-label="Company profile completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $verificationCompletionPercentage }}">
            <div class="h-full rounded-full bg-amber-800" style="width: {{ $verificationCompletionPercentage }}%"></div>
        </div>
    </section>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">@foreach([['Trips', $tripCount], ['Published', $publishedTripCount], ['Bookings', $bookingCount], ['Pending', $pendingBookingCount], ['Customers', $customerCount], ['Earnings', 'KES '.number_format($earnings)]] as [$label, $value])<div class="rounded border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-black text-slate-950">{{ $value }}</p></div>@endforeach</div>
    <div class="grid gap-8 xl:grid-cols-[1.35fr_1fr]"><section class="rounded border border-slate-200 bg-white p-5"><div class="flex items-center justify-between"><h2 class="text-lg font-black">Recent bookings</h2><a class="text-sm font-bold text-emerald-800" href="{{ route('operator.bookings.index') }}">View all</a></div><div class="mt-4 divide-y divide-slate-100">@forelse($recentBookings as $booking)<div class="flex items-center justify-between gap-4 py-4"><div><p class="font-bold">{{ $booking->traveler->name }}</p><p class="text-sm text-slate-500">{{ $booking->trip->name }} · {{ $booking->reference }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $booking->status->value }}</span></div>@empty<p class="py-8 text-sm text-slate-500">No bookings yet.</p>@endforelse</div></section><section class="rounded border border-slate-200 bg-white p-5"><div class="flex items-center justify-between"><h2 class="text-lg font-black">Your trips</h2><a class="text-sm font-bold text-emerald-800" href="{{ route('operator.trips.create') }}">Create trip</a></div><div class="mt-4 grid gap-3">@forelse($trips as $trip)<a href="{{ route('operator.trips.edit', $trip) }}" class="rounded bg-slate-50 p-4 hover:bg-emerald-50"><div class="flex justify-between gap-3"><strong>{{ $trip->name }}</strong><span class="text-xs font-bold">{{ $trip->bookings_count }} bookings</span></div><p class="mt-1 text-xs text-slate-500">{{ $trip->status->value }} · {{ $trip->verification_status->value }}</p></a>@empty<p class="py-8 text-sm text-slate-500">Create your first trip.</p>@endforelse</div></section></div>
</div>
@endsection
