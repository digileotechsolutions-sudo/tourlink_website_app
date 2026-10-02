@extends('layouts.admin')

@section('title', 'Booking '.$booking->reference.' | Havenedge Tourlink Admin')

@section('content')
    @php
        $hasSettledPayment = $booking->payments->contains(fn ($payment): bool => in_array($payment->status, [\App\PaymentStatus::Successful, \App\PaymentStatus::Refunded, \App\PaymentStatus::PartiallyRefunded], true));
        $hasPaymentInFlight = $booking->payments->contains(fn ($payment): bool => $payment->status === \App\PaymentStatus::Processing || ($payment->status === \App\PaymentStatus::Pending && (in_array($payment->payment_method, [\App\PaymentMethod::Card, \App\PaymentMethod::BankTransfer], true) || $payment->phone_number !== null || $payment->transaction_reference !== null || $payment->daraja_checkout_request_id !== null || $payment->provider_response !== null)));
        $canAdminCancel = in_array($booking->status, [\App\BookingStatus::Pending, \App\BookingStatus::Confirmed], true) && ! $hasSettledPayment && ! $hasPaymentInFlight;
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

                <section class="mt-7 rounded-2xl border border-slate-200 bg-slate-50 p-5" aria-labelledby="booking-payment-summary-heading">
                    <h2 id="booking-payment-summary-heading" class="text-lg font-extrabold text-slate-950">Booking payment summary</h2>
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-4">
                        <div><dt class="text-slate-500">Booking total</dt><dd class="mt-1 font-bold">{{ $booking->currency }} {{ number_format($paymentSummary['total']) }}</dd></div>
                        <div><dt class="text-slate-500">Total paid</dt><dd class="mt-1 font-bold">{{ $booking->currency }} {{ number_format($paymentSummary['paid']) }}</dd></div>
                        <div><dt class="text-slate-500">Remaining balance</dt><dd class="mt-1 font-bold">{{ $booking->currency }} {{ number_format($paymentSummary['balance']) }}</dd></div>
                        <div><dt class="text-slate-500">Payment status</dt><dd class="mt-1 font-bold">{{ str($paymentSummary['status'])->replace('_', ' ')->title() }}</dd></div>
                    </dl>
                    @if ($paymentSummary['balance'] > 0 && ! in_array($booking->status, [\App\BookingStatus::Cancelled, \App\BookingStatus::Refunded], true))
                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            <form method="POST" action="{{ route('admin.bookings.cash-payments.store', $booking) }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4">
                                @csrf
                                <h3 class="font-bold">Record cash payment</h3>
                                <label class="grid gap-1 text-sm font-semibold">Amount ({{ $booking->currency }})
                                    <input name="amount" type="number" min="1" max="{{ $paymentSummary['balance'] }}" required class="min-h-11 rounded border border-slate-300 px-3">
                                </label>
                                <label class="grid gap-1 text-sm font-semibold">Notes
                                    <textarea name="notes" rows="2" maxlength="1000" class="rounded border border-slate-300 px-3 py-2"></textarea>
                                </label>
                                <button class="min-h-11 rounded bg-ink px-4 font-bold text-white">Record cash received</button>
                            </form>
                            <form method="POST" action="{{ route('admin.bookings.bank-transfers.store', $booking) }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4">
                                @csrf
                                <h3 class="font-bold">Record bank transfer</h3>
                                <label class="grid gap-1 text-sm font-semibold">Amount ({{ $booking->currency }})
                                    <input name="amount" type="number" min="1" max="{{ $paymentSummary['balance'] }}" required class="min-h-11 rounded border border-slate-300 px-3">
                                </label>
                                <label class="grid gap-1 text-sm font-semibold">Bank reference
                                    <input name="reference" maxlength="120" required class="min-h-11 rounded border border-slate-300 px-3">
                                </label>
                                <label class="grid gap-1 text-sm font-semibold">Notes
                                    <textarea name="notes" rows="2" maxlength="1000" class="rounded border border-slate-300 px-3 py-2"></textarea>
                                </label>
                                <button class="min-h-11 rounded border border-slate-300 px-4 font-bold">Save as pending verification</button>
                            </form>
                        </div>
                    @elseif ($paymentSummary['balance'] > 0)
                        <p class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">This booking is closed and cannot accept another payment.</p>
                    @endif
                </section>

                <section class="mt-7" aria-labelledby="payment-history-heading">
                    <div class="flex items-baseline justify-between border-b border-slate-200 pb-3"><h2 id="payment-history-heading" class="text-lg font-extrabold text-slate-950">Payments and refunds</h2><span class="text-sm text-slate-500">{{ $booking->payments->count() }} payment(s)</span></div>
                    @forelse($booking->payments as $payment)
                        <article class="border-b border-slate-100 py-4">
                            @php($refundableAmount = max(0, $payment->amount - $payment->refunds->whereIn('status', ['PENDING', 'COMPLETED'])->sum('amount')))
                            <div class="flex flex-wrap justify-between gap-2"><p class="font-semibold">{{ str($payment->payment_method?->value ?? 'MPESA')->replace('_', ' ')->title() }} · {{ str($payment->status->value)->replace('_', ' ')->title() }}</p><p class="font-bold">{{ $booking->currency }} {{ number_format($payment->amount) }}</p></div>
                            <p class="mt-1 text-xs text-slate-500">{{ $payment->transaction_reference ?: $payment->merchant_reference }} · {{ $payment->paid_at?->format('M j, Y H:i') ?? 'Unpaid' }}@if($payment->receiver) · Recorded by {{ $payment->receiver->name }}@endif</p>
                            @if(in_array($payment->status, [\App\PaymentStatus::Successful, \App\PaymentStatus::Refunded, \App\PaymentStatus::PartiallyRefunded], true))
                                <a class="mt-2 inline-block text-xs font-bold text-emerald-800 hover:underline" href="{{ route('payments.receipt.download', $payment) }}">Download receipt</a>
                            @endif
                            @if($payment->payment_method === \App\PaymentMethod::BankTransfer && $payment->status === \App\PaymentStatus::Pending)
                                <form method="POST" action="{{ route('admin.payments.verify-bank-transfer', $payment) }}" class="mt-3">@csrf @method('PATCH')<button class="min-h-9 rounded border border-emerald-700 px-3 text-xs font-bold text-emerald-900">Verify bank transfer</button></form>
                            @endif
                            @if($payment->payment_method === \App\PaymentMethod::Card && in_array($payment->status, [\App\PaymentStatus::Pending, \App\PaymentStatus::Processing, \App\PaymentStatus::Failed], true) && is_string(data_get($payment->metadata, 'order_tracking_id')))
                                <form method="POST" action="{{ route('admin.payments.verify-pesapal', $payment) }}" class="mt-3">@csrf<button class="min-h-9 rounded border border-blue-700 px-3 text-xs font-bold text-blue-900">Check Pesapal status</button></form>
                            @endif
                            @foreach($payment->refunds as $refund)
                                <div class="mt-3 rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                                    <p>Refund {{ $refund->reference }} · {{ $refund->status }} · {{ $booking->currency }} {{ number_format($refund->amount) }}</p>
                                    <p class="mt-1">{{ $refund->reason }}</p>
                                    @if($refund->status === 'PENDING')
                                        <form method="POST" action="{{ route('admin.refunds.complete', $refund) }}" class="mt-2 flex flex-wrap gap-2">@csrf @method('PATCH')<label class="sr-only" for="refund-provider-reference-{{ $refund->id }}">Provider refund reference</label><input id="refund-provider-reference-{{ $refund->id }}" name="provider_reference" maxlength="120" required placeholder="Provider refund reference" class="min-h-9 rounded border border-slate-300 px-2 text-xs"><button class="min-h-9 rounded bg-ink px-3 font-bold text-white">Mark refund completed</button></form>
                                    @endif
                                </div>
                            @endforeach
                            @if($refundableAmount > 0 && in_array($payment->status, [\App\PaymentStatus::Successful, \App\PaymentStatus::PartiallyRefunded], true))
                                <form method="POST" action="{{ route('admin.payments.refunds.store', $payment) }}" class="mt-3 grid gap-2 rounded border border-slate-200 p-3 sm:grid-cols-[10rem_minmax(0,1fr)_auto]">@csrf
                                    <label class="grid gap-1 text-xs font-semibold">Refund amount
                                        <input name="amount" type="number" min="1" max="{{ $refundableAmount }}" required class="min-h-9 rounded border border-slate-300 px-2">
                                    </label>
                                    <label class="grid gap-1 text-xs font-semibold">Reason
                                        <input name="reason" maxlength="1000" required class="min-h-9 rounded border border-slate-300 px-2">
                                    </label>
                                    <button class="min-h-9 self-end rounded border border-amber-300 px-3 text-xs font-bold text-amber-900">Record pending refund</button>
                                </form>
                            @endif
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
                @elseif($hasSettledPayment || $hasPaymentInFlight)
                    <p class="mt-6 border-t border-slate-200 pt-4 text-xs leading-5 text-slate-600">{{ $hasSettledPayment ? 'A successful or refunded payment exists. Use the refund workflow to resolve this booking financially.' : 'A payment is processing or awaiting bank verification. Resolve that payment before cancelling the booking.' }}</p>
                @endif
            </aside>
        </div>
    </div>
@endsection