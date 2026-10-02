@extends('layouts.admin')

@php
    $sections = [
        'payments' => ['Payments', 'admin.payments.index'],
        'refunds' => ['Refunds', 'admin.refunds.index'],
        'commissions' => ['Commissions', 'admin.commissions.index'],
        'payouts' => ['Payouts', 'admin.payouts.index'],
    ];
    $titles = ['payments' => 'Payments', 'refunds' => 'Refunds', 'commissions' => 'Commissions', 'payouts' => 'Payouts'];
@endphp

@section('title', $titles[$section].' | Havenedge Tourlink Admin')

@section('content')
    <div class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="border-b border-slate-200 pb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Finance</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $titles[$section] }}</h1>
            <p class="mt-1 text-sm text-slate-600">Transaction and settlement records.</p>
        </header>

        <nav class="mt-5 flex flex-wrap gap-2 border-b border-slate-200" aria-label="Finance sections">
            @foreach($sections as $key => [$label, $routeName])
                <a href="{{ route($routeName) }}" @if($section === $key) aria-current="page" @endif class="border-b-2 px-3 py-2.5 text-sm font-bold {{ $section === $key ? 'border-emerald-800 text-emerald-900' : 'border-transparent text-slate-500 hover:text-slate-900' }}">{{ $label }}</a>
            @endforeach
        </nav>

        @if($section === 'payments')
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <article class="rounded border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase text-slate-500">Cash collected</p><p class="mt-1 text-xl font-black">KES {{ number_format($metrics['cash_collected']) }}</p></article>
                <article class="rounded border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase text-slate-500">Verified bank transfers</p><p class="mt-1 text-xl font-black">KES {{ number_format($metrics['bank_collected']) }}</p></article>
                <article class="rounded border border-amber-200 bg-amber-50 p-4"><p class="text-xs font-bold uppercase text-amber-800">Bank transfers awaiting verification</p><p class="mt-1 text-xl font-black text-amber-950">{{ number_format($metrics['pending_bank_count']) }}</p></article>
            </div>
        @endif

        <form method="GET" class="mt-5 flex flex-wrap items-end gap-3 border-b border-slate-200 pb-5">
            <label class="sr-only" for="finance-search">Search {{ strtolower($titles[$section]) }}</label>
            <input id="finance-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search references or names" class="min-h-10 w-full max-w-md rounded border border-slate-300 px-3 text-sm">
            @if($section === 'payments')
                <label class="sr-only" for="payment-status">Payment status</label>
                <select id="payment-status" name="status" class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm"><option value="">All payment statuses</option>@foreach(\App\PaymentStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>@endforeach</select>
                <label class="sr-only" for="payment-method">Payment method</label>
                <select id="payment-method" name="method" class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm"><option value="">All payment methods</option>@foreach(\App\PaymentMethod::cases() as $method)<option value="{{ $method->value }}" @selected(($filters['method'] ?? '') === $method->value)>{{ str($method->value)->replace('_', ' ')->title() }}</option>@endforeach</select>
                <label class="sr-only" for="payment-staff">Recorded by admin</label>
                <select id="payment-staff" name="staff" class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm"><option value="">All admins</option>@foreach($staffOptions as $staff)<option value="{{ $staff->id }}" @selected(($filters['staff'] ?? '') === $staff->id)>{{ $staff->name }}</option>@endforeach</select>
                <label class="grid gap-1 text-xs font-semibold text-slate-600">Recorded from<input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm"></label>
                <label class="grid gap-1 text-xs font-semibold text-slate-600">Recorded to<input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm"></label>
            @endif
            <button class="min-h-10 rounded border border-slate-300 px-4 text-sm font-bold text-slate-800 hover:bg-slate-50">Filter</button>
            <a href="{{ route($sections[$section][1]) }}" class="grid min-h-10 place-items-center px-2 text-sm font-semibold text-slate-600">Clear</a>
        </form>

        <div class="mt-2 overflow-x-auto">
            @if($section === 'payments')
                <table class="w-full min-w-[1180px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500"><tr><th class="py-3 pr-4">Payment reference</th><th class="py-3 pr-4">Traveler / booking</th><th class="py-3 pr-4">Item</th><th class="py-3 pr-4">Method / admin</th><th class="py-3 pr-4">Amount</th><th class="py-3 pr-4">Status</th><th class="py-3 pr-4">Refunds</th><th class="py-3 pr-4">Paid at</th><th class="py-3">Receipt</th></tr></thead>
                    <tbody>
                        @forelse($records as $payment)
                            <tr class="border-b border-slate-100 align-top">
                                <td class="py-4 pr-4"><p class="font-semibold">{{ $payment->transaction_reference ?: 'No receipt yet' }}</p><p class="mt-1 text-xs text-slate-500">{{ $payment->provider }} · Merchant {{ $payment->merchant_reference }}</p></td>
                                <td class="py-4 pr-4">{{ $payment->booking?->traveler?->name }}<p class="mt-1 text-xs"><a href="{{ route('admin.bookings.show', $payment->booking) }}" class="text-emerald-800 hover:underline">{{ $payment->booking?->reference }}</a></p></td>
                                <td class="py-4 pr-4">{{ $payment->booking?->trip?->name ?? $payment->booking?->vehicle?->name ?? 'Unavailable' }}</td>
                                <td class="py-4 pr-4">{{ str($payment->payment_method?->value ?? 'MPESA')->replace('_', ' ')->title() }}<p class="mt-1 text-xs text-slate-500">{{ $payment->receiver?->name ?? '—' }}</p></td>
                                <td class="whitespace-nowrap py-4 pr-4 font-bold">KES {{ number_format($payment->amount) }}</td>
                                <td class="py-4 pr-4">{{ str($payment->status->value)->replace('_', ' ')->title() }}</td>
                                <td class="py-4 pr-4">KES {{ number_format($payment->refunds->sum('amount')) }}</td>
                                <td class="whitespace-nowrap py-4 pr-4 text-xs text-slate-600">{{ $payment->paid_at?->format('M j, Y H:i') ?? '—' }}</td>
                                <td class="py-4">
                                    @if(in_array($payment->status, [\App\PaymentStatus::Successful, \App\PaymentStatus::Refunded, \App\PaymentStatus::PartiallyRefunded], true))
                                        <a href="{{ route('payments.receipt.download', $payment) }}" class="font-bold text-emerald-800 hover:underline">Download receipt</a>
                                    @elseif($payment->payment_method === \App\PaymentMethod::Card && in_array($payment->status, [\App\PaymentStatus::Pending, \App\PaymentStatus::Processing, \App\PaymentStatus::Failed], true) && is_string(data_get($payment->metadata, 'order_tracking_id')))
                                        <form method="POST" action="{{ route('admin.payments.verify-pesapal', $payment) }}">@csrf<button class="font-bold text-blue-800 hover:underline">Check Pesapal status</button></form>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty<tr><td colspan="9" class="py-10 text-center text-sm text-slate-600">No payments match these filters.</td></tr>@endforelse
                    </tbody>
                </table>
            @elseif($section === 'refunds')
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500"><tr><th class="py-3 pr-4">Recorded</th><th class="py-3 pr-4">Traveler</th><th class="py-3 pr-4">Payment / booking</th><th class="py-3 pr-4">Amount</th><th class="py-3 pr-4">Status</th><th class="py-3 pr-4">Provider reference</th><th class="py-3">Reason</th></tr></thead>
                    <tbody>
                        @forelse($records as $refund)
                            <tr class="border-b border-slate-100 align-top"><td class="whitespace-nowrap py-4 pr-4">{{ $refund->created_at?->format('M j, Y H:i') }}</td><td class="py-4 pr-4">{{ $refund->payment?->booking?->traveler?->name }}</td><td class="py-4 pr-4">{{ $refund->payment?->merchant_reference }}<p class="mt-1 text-xs"><a href="{{ route('admin.bookings.show', $refund->payment->booking) }}" class="text-emerald-800 hover:underline">{{ $refund->payment?->booking?->reference }}</a></p></td><td class="whitespace-nowrap py-4 pr-4 font-bold">KES {{ number_format($refund->amount) }}</td><td class="py-4 pr-4">{{ str($refund->status)->replace('_', ' ')->title() }}</td><td class="py-4 pr-4">{{ $refund->provider_reference ?: '—' }}</td><td class="max-w-lg py-4 text-slate-600">{{ $refund->reason }}</td></tr>
                        @empty<tr><td colspan="7" class="py-10 text-center text-sm text-slate-600">No refund records found.</td></tr>@endforelse
                    </tbody>
                </table>
            @elseif($section === 'commissions')
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500"><tr><th class="py-3 pr-4">Booking</th><th class="py-3 pr-4">Provider</th><th class="py-3 pr-4">Gross</th><th class="py-3 pr-4">Rate</th><th class="py-3 pr-4">Commission</th><th class="py-3 pr-4">Provider amount</th><th class="py-3">Payout status</th></tr></thead>
                    <tbody>
                        @forelse($records as $commission)
                            @php($booking = $commission->booking)
                            <tr class="border-b border-slate-100"><td class="py-4 pr-4"><a href="{{ route('admin.bookings.show', $booking) }}" class="font-semibold text-emerald-800 hover:underline">{{ $booking?->reference }}</a></td><td class="py-4 pr-4">{{ $booking?->trip?->operator?->name ?? $booking?->vehicle?->owner?->name }}</td><td class="py-4 pr-4">KES {{ number_format($commission->gross_amount) }}</td><td class="py-4 pr-4">{{ number_format($commission->rate * 100, 1) }}%</td><td class="py-4 pr-4 font-bold">KES {{ number_format($commission->commission_amount) }}</td><td class="py-4 pr-4">KES {{ number_format($commission->provider_amount) }}</td><td class="py-4">{{ str($commission->payout_status)->title() }}</td></tr>
                        @empty<tr><td colspan="7" class="py-10 text-center text-sm text-slate-600">No commissions found.</td></tr>@endforelse
                    </tbody>
                </table>
            @else
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500"><tr><th class="py-3 pr-4">Payout date</th><th class="py-3 pr-4">Provider</th><th class="py-3 pr-4">Role</th><th class="py-3 pr-4">Reference</th><th class="py-3 pr-4">Amount</th><th class="py-3">Status</th></tr></thead>
                    <tbody>
                        @forelse($records as $payout)
                            <tr class="border-b border-slate-100"><td class="whitespace-nowrap py-4 pr-4">{{ $payout->created_at?->format('M j, Y H:i') }}</td><td class="py-4 pr-4">{{ $payout->provider_name }}<p class="mt-1 text-xs text-slate-500">{{ $payout->provider_email }}</p></td><td class="py-4 pr-4">{{ str($payout->provider_role)->replace('_', ' ')->title() }}</td><td class="py-4 pr-4">{{ $payout->reference ?: '—' }}</td><td class="py-4 pr-4 font-bold">KES {{ number_format($payout->amount) }}</td><td class="py-4">{{ str($payout->status)->title() }}</td></tr>
                        @empty<tr><td colspan="6" class="py-10 text-center text-sm text-slate-600">No payouts found.</td></tr>@endforelse
                    </tbody>
                </table>
            @endif
        </div>
        <div class="mt-6">{{ $records->links() }}</div>
    </div>
@endsection