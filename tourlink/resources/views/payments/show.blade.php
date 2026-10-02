@extends('layouts.app')

@section('title', 'Booking payment | Havenedge Tourlink')

@section('content')
    <main class="container-page py-8 sm:py-12">
        <header class="mb-6">
            <a href="{{ route('traveler.bookings') }}" class="text-sm font-bold text-ink hover:underline">← My bookings</a>
            <p class="mt-5 text-xs font-bold uppercase tracking-wider text-emerald-800">Secure payment</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Booking {{ $booking->reference }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ $booking->trip?->name ?? $booking->vehicle?->name ?? 'TourLink booking' }}
                @if ($booking->trip?->destination) · {{ $booking->trip->destination->name }} @endif
                @if ($booking->vehicle?->destination) · {{ $booking->vehicle->destination->name }} @endif
            </p>
        </header>

        @if (session('status'))
            <p role="status" class="mb-5 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-950">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-extrabold">Booking balance</h2>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">Booking total</dt><dd class="mt-1 font-bold">{{ $booking->currency }} {{ number_format($summary['total']) }}</dd></div>
                    <div><dt class="text-slate-500">Total paid</dt><dd class="mt-1 font-bold">{{ $booking->currency }} {{ number_format($summary['paid']) }}</dd></div>
                    <div><dt class="text-slate-500">Remaining balance</dt><dd class="mt-1 text-xl font-black text-ink">{{ $booking->currency }} {{ number_format($summary['balance']) }}</dd></div>
                    <div><dt class="text-slate-500">Payment status</dt><dd class="mt-1 font-bold">{{ str($summary['status'])->replace('_', ' ')->title() }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-extrabold">Make a payment</h2>
                @if ($summary['balance'] > 0 && $canPay)
                    <label class="mt-4 grid gap-1 text-sm font-semibold" for="payment-amount">Amount to pay ({{ $booking->currency }})</label>
                    <input id="payment-amount" form="mpesa-payment" name="amount" type="number" min="1" max="{{ $summary['balance'] }}" value="{{ $summary['balance'] }}" required class="mt-1 min-h-11 w-full rounded border border-slate-300 px-3">
                    <label class="mt-4 grid gap-1 text-sm font-semibold" for="payment-phone">M-Pesa phone number</label>
                    <input id="payment-phone" form="mpesa-payment" name="phone_number" type="tel" autocomplete="tel" placeholder="07xx xxx xxx" required class="mt-1 min-h-11 w-full rounded border border-slate-300 px-3">
                    <form id="mpesa-payment" method="POST" action="{{ route('payments.mpesa', $booking) }}" class="mt-3">
                        @csrf
                        <button class="min-h-12 w-full rounded-xl bg-ink px-4 font-bold text-white hover:bg-emerald-950">Pay with M-Pesa</button>
                    </form>
                    <form method="POST" action="{{ route('payments.card', $booking) }}" class="mt-3">
                        @csrf
                        <label class="grid gap-1 text-sm font-semibold" for="card-payment-amount">Card payment amount ({{ $booking->currency }})</label>
                        <input id="card-payment-amount" name="amount" type="number" min="1" max="{{ $summary['balance'] }}" value="{{ $summary['balance'] }}" required class="mb-3 min-h-11 w-full rounded border border-slate-300 px-3">
                        <button class="min-h-12 w-full rounded-xl border border-slate-300 px-4 font-bold text-slate-800 hover:bg-slate-50">Pay securely by card</button>
                    </form>
                    <div class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-600">
                        <p class="font-bold text-slate-800">Cash — pay at the Havenedge Tourlink office</p>
                        <p class="mt-1">Cash is recorded by authorized staff after it is received. Contact support for office directions.</p>
                        <p class="mt-3"><a href="{{ route('contact') }}" class="font-bold text-ink underline">Contact support</a> for bank-transfer instructions.</p>
                    </div>
                @elseif ($summary['balance'] > 0)
                    <p class="mt-4 rounded bg-amber-50 p-4 text-sm font-semibold text-amber-950">This booking is closed and cannot accept further payments. Contact support if you need help with its balance.</p>
                @else
                    <p class="mt-4 rounded bg-emerald-50 p-4 text-sm font-semibold text-emerald-950">This booking has no outstanding balance.</p>
                @endif
            </div>
        </section>

        <section class="mt-8" aria-labelledby="payment-history-heading">
            <h2 id="payment-history-heading" class="border-b border-slate-200 pb-3 text-lg font-extrabold">Payment history</h2>
            @forelse ($booking->payments->sortByDesc('created_at') as $payment)
                <article class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 py-4">
                    <div>
                        <p class="font-bold">{{ str($payment->payment_method?->value ?? $payment->provider)->replace('_', ' ')->title() }} · {{ str($payment->status->value)->replace('_', ' ')->title() }}</p>
                        <p class="mt-1 text-xs text-slate-500">Reference: {{ $payment->transaction_reference ?: $payment->merchant_reference }} · {{ $payment->paid_at?->format('M j, Y H:i') ?? 'Awaiting confirmation' }}</p>
                        @if ($payment->status->value === 'SUCCESSFUL' || in_array($payment->status->value, ['REFUNDED', 'PARTIALLY_REFUNDED'], true))
                            <a href="{{ route('payments.receipt', $payment) }}" class="mt-2 inline-flex min-h-10 items-center font-bold text-ink underline">View receipt {{ $payment->receipt_number }}</a>
                        @endif
                    </div>
                    <p class="font-extrabold">{{ $booking->currency }} {{ number_format($payment->amount) }}</p>
                </article>
            @empty
                <p class="py-5 text-sm text-slate-600">No payment attempts yet.</p>
            @endforelse
        </section>
    </main>
@endsection
