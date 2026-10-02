@extends('layouts.admin')

@section('title', 'Booking '.$booking->reference.' | Havenedge Tourlink Admin')

@section('content')
    @php
        $hasSettledPayment = $booking->payments->contains(fn ($payment): bool => in_array($payment->status, [\App\PaymentStatus::Successful, \App\PaymentStatus::Refunded, \App\PaymentStatus::PartiallyRefunded], true));
        $canAdminCancel = in_array($booking->status, [\App\BookingStatus::Pending, \App\BookingStatus::Confirmed], true) && ! $hasSettledPayment;
    @endphp
    <div class="mx-auto max-w-[1200px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6">
            <div><a href="{{ route('admin.bookings.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">Back to bookings</a><p class="mt-4 text-xs font-bold uppercase tracking-wider text-emerald-800">{{ str($booking->type->value)->title() }} booking</p><h1 class="mt-1 text-3xl font-black text-slate-950">{{ $booking->reference }}</h1></div>
            <span class="border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-800">{{ str($booking->status->value)->replace('_', ' ')->title() }}</span>
        </header>
        @if (session('status'))<p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950"><p class="font-bold">The booking action could not be completed.</p>@foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div>@endif

        <div class="mt-7 grid gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(18rem,0.8fr)]">
            <div>
                <section class="border-y border-slate-200 py-5" aria-labelledby="booking-summary-heading">
                    <h2 id="booking-summary-heading" class="text-lg font-extrabold text-slate-950">Reservation</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div><dt class="text-xs font-semibold text-slate-500">Traveler</dt><dd class="mt-1 font-semibold">{{ $booking->traveler?->name ?? 'Unknown traveler' }}</dd><dd class="text-sm text-slate-600">{{ $booking->traveler?->email }}</dd></div>
                        <div><dt class="text-xs font-semibold text-slate-500">Reserved item</dt><dd class="mt-1 font-semibold">{{ $booking->trip?->name ?? $booking->vehicle?->name ?? 'Unavailable' }}</dd><dd class="text-sm text-slate-600">{{ $booking->trip?->destination?->name ?? $booking->vehicle?->destination?->name ?? '' }}</dd></div>
                        <div><dt class="text-xs font-semibold text-slate-500">Travel dates</dt><dd class="mt-1">{{ $booking->start_date?->format('M j, Y H:i') }} to {{ $booking->end_date?->format('M j, Y H:i') }}</dd></div>
                        <div><dt class="text-xs font-semibold text-slate-500">Party size</dt><dd class="mt-1">{{ $booking->travelers }} traveler(s)</dd></div>
                        <div><dt class="text-xs font-semibold text-slate-500">Pickup location</dt><dd class="mt-1">{{ $booking->pickup_location ?: 'Not specified' }}</dd></div>
                        @if($booking->type === \App\BookingType::Vehicle)<div><dt class="text-xs font-semibold text-slate-500">Driver requested</dt><dd class="mt-1">{{ $booking->driver_required ? 'Yes' : 'No' }}</dd></div>@endif
                        <div class="sm:col-span-2"><dt class="text-xs font-semibold text-slate-500">Traveler notes</dt><dd class="mt-1 whitespace-pre-wrap text-sm">{{ $booking->notes ?: 'None' }}</dd></div>
                    </dl>
                </section>

                <section class="mt-7" aria-labelledby="payment-history-heading">
                    <div class="flex items-baseline justify-between border-b border-slate-200 pb-3"><h2 id="payment-history-heading" class="text-lg font-extrabold text-slate-950">Payments and refunds</h2><span class="text-sm text-slate-500">{{ $booking->payments->count() }} payment(s)</span></div>
                    @forelse($booking->payments as $payment)
                        <article class="border-b border-slate-100 py-4">
                            <div class="flex flex-wrap justify-between gap-2"><p class="font-semibold">{{ $payment->provider }} · {{ str($payment->status->value)->replace('_', ' ')->title() }}</p><p class="font-bold">{{ $booking->currency }} {{ number_format($payment->amount) }}</p></div>
                            <p class="mt-1 text-xs text-slate-500">{{ $payment->transaction_reference ?: $payment->merchant_reference }} · {{ $payment->paid_at?->format('M j, Y H:i') ?? 'Unpaid' }}</p>
                            @foreach($payment->refunds as $refund)<p class="mt-2 text-xs text-slate-600">Refund: {{ $booking->currency }} {{ number_format($refund->amount) }} · {{ $refund->reason }}</p>@endforeach
                        </article>
                    @empty<p class="py-5 text-sm text-slate-600">No payment records.</p>@endforelse
                </section>
            </div>

            <aside class="h-fit border border-slate-200 bg-white p-5" aria-labelledby="booking-total-heading">
                <h2 id="booking-total-heading" class="text-lg font-extrabold text-slate-950">Amount summary</h2>
                <dl class="mt-4 space-y-3 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-600">Base amount</dt><dd>{{ $booking->currency }} {{ number_format($booking->base_amount) }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-600">Fees</dt><dd>{{ $booking->currency }} {{ number_format($booking->fees) }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-600">Discount</dt><dd>- {{ $booking->currency }} {{ number_format($booking->discount) }}</dd></div><div class="flex justify-between gap-3 border-t border-slate-200 pt-3 text-base font-extrabold"><dt>Total</dt><dd>{{ $booking->currency }} {{ number_format($booking->total_amount) }}</dd></div></dl>
                @if($canAdminCancel)
                    <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" class="mt-6 border-t border-slate-200 pt-5" onsubmit="return confirm('Cancel this booking and restore any trip seats?')">
                        @csrf @method('PATCH')
                        <label for="cancel-reason" class="grid gap-1 text-sm font-semibold text-slate-700">Cancellation reason<textarea id="cancel-reason" name="reason" required minlength="5" maxlength="1000" rows="3" class="rounded border border-slate-300 px-3 py-2 text-sm font-normal"></textarea></label>
                        <button class="mt-3 min-h-10 rounded border border-red-300 px-4 text-sm font-bold text-red-700 hover:bg-red-50">Cancel booking</button>
                    </form>
                @elseif($hasSettledPayment)
                    <p class="mt-6 border-t border-slate-200 pt-4 text-xs leading-5 text-slate-600">A successful or refunded payment exists. Use the refund workflow to resolve this booking financially.</p>
                @endif
            </aside>
        </div>
    </div>
@endsection