@extends('layouts.vehicle-owner')
@section('title', 'Vehicle owner overview | TourLink')
@section('content')
<div class="mx-auto max-w-7xl space-y-6 p-4 sm:space-y-8 sm:p-8">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-ink">Vehicle owner overview</p>
            <h1 class="mt-2 text-3xl font-black">Manage your fleet</h1>
            <p class="mt-2 text-slate-600">List vehicles, respond to travelers, and track your earnings.</p>
        </div>
        <a class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-ink px-5 text-sm font-bold text-white shadow-soft sm:w-auto" href="{{ route('vehicle-owner.vehicles.create') }}">Add vehicle</a>
    </header>

    <section class="grid gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-soft sm:p-5" aria-labelledby="verification-reminder-heading">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 id="verification-reminder-heading" class="text-lg font-black text-slate-950">Profile verification: {{ $verificationCompletionPercentage }}% complete</h2>
                <p class="mt-1 text-sm text-amber-950">{{ $verificationCompletionPercentage < 100 ? 'Your account starts at 50%. Upload each required identity and vehicle document to complete verification.' : 'Your account and required identity and vehicle documents are complete.' }}</p>
            </div>
            @if ($verificationCompletionPercentage < 100)
                <a class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-ink px-5 text-sm font-bold text-white sm:w-auto" href="{{ route('vehicle-owner.profile') }}">Complete profile</a>
            @endif
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-white" role="progressbar" aria-label="Profile verification completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $verificationCompletionPercentage }}">
            <div class="h-full rounded-full bg-[#FF467A]" style="width: {{ $verificationCompletionPercentage }}%"></div>
        </div>
    </section>

    <section class="dashboard-kpi-grid grid gap-3 sm:grid-cols-2 xl:grid-cols-6" aria-label="Fleet statistics">
        @foreach ([['Vehicles', $vehicleCount], ['Published', $publishedVehicleCount], ['Bookings', $bookingCount], ['Pending', $pendingBookingCount], ['Earnings', 'KES '.number_format($earnings)], ['Rating', $rating ? number_format($rating, 1).'/5' : '—']] as [$label, $value])
            <article class="dashboard-kpi-card rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-black text-ink">{{ $value }}</p>
            </article>
        @endforeach
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft sm:p-5" aria-labelledby="recent-bookings-heading">
        <div class="flex items-center justify-between gap-3">
            <h2 id="recent-bookings-heading" class="text-lg font-black">Recent booking requests</h2>
            <a class="inline-flex min-h-11 shrink-0 items-center rounded-lg px-3 text-sm font-bold text-ink hover:bg-slate-50" href="{{ route('vehicle-owner.bookings.index') }}">View all</a>
        </div>
        <div class="mt-3 divide-y divide-slate-100">
            @forelse ($recentBookings as $booking)
                <article class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div class="min-w-0">
                        <p class="break-words font-bold">{{ $booking->traveler->name }}</p>
                        <p class="mt-1 break-words text-sm text-slate-500">{{ $booking->vehicle->name }} · {{ $booking->reference }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $booking->status->value }}</span>
                </article>
            @empty
                <p class="py-8 text-sm text-slate-500">No vehicle bookings yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
