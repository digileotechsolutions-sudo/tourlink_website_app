@extends('layouts.traveler')

@section('title', 'My bookings | Havenedge Tourlink')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-8">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-emerald-800">Your journeys</p>
            <h1 class="mt-2 text-3xl font-black">My bookings</h1>
        </div>

        <div class="grid gap-4">
            @forelse($bookings as $booking)
                <article class="rounded border border-slate-200 bg-white p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $booking->reference }} · {{ $booking->status->value }}</p>
                            <h2 class="mt-2 text-lg font-black">{{ $booking->trip?->name ?? $booking->vehicle?->name ?? 'Journey' }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $booking->start_date->format('M j, Y') }} to {{ $booking->end_date->format('M j, Y') }} · KES {{ number_format($booking->total_amount) }}</p>
                            <p class="mt-2 text-sm text-slate-600">Paid KES {{ number_format($booking->payment_summary['paid']) }} · Balance KES {{ number_format($booking->payment_summary['balance']) }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-bold">{{ str($booking->payment_summary['status'])->replace('_', ' ')->title() }}</span>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <a class="rounded bg-emerald-800 px-4 py-2 text-sm font-bold text-white" href="{{ route('payments.show', $booking) }}">
                            {{ $booking->payment_summary['balance'] > 0 && ! in_array($booking->status, [\App\BookingStatus::Cancelled, \App\BookingStatus::Refunded], true) ? 'Pay balance' : 'Payment details' }}
                        </a>
                        <a class="rounded border border-slate-300 px-4 py-2 text-sm font-bold text-slate-800" href="{{ route('support.index', ['booking' => $booking->reference]) }}">Get help with this booking</a>
                        @if(in_array($booking->status, [\App\BookingStatus::Pending, \App\BookingStatus::Confirmed], true) && $booking->payment_summary['paid'] === 0 && ! $booking->payment_in_flight)
                            <form method="POST" action="{{ route('traveler.bookings.cancel', $booking) }}">
                                @csrf
                                @method('PATCH')
                                <button class="rounded border border-red-200 px-4 py-2 text-sm font-bold text-red-700">Cancel booking</button>
                            </form>
                        @endif
                        @if($booking->status === \App\BookingStatus::Completed)
                            <a class="rounded border border-slate-300 px-4 py-2 text-sm font-bold" href="{{ route('traveler.reviews.create', $booking) }}">Leave review</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    No bookings yet. <a class="font-bold text-emerald-800" href="{{ route('trips.index') }}">Find a trip</a>.
                </div>
            @endforelse
        </div>

        {{ $bookings->links() }}
    </div>
@endsection
