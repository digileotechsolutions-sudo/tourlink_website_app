@extends('layouts.admin')

@section('title', 'Bookings | Havenedge Tourlink Admin')

@section('content')
    <div class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6">
            <div><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Operations</p><h1 class="mt-2 text-3xl font-black text-slate-950">Bookings</h1><p class="mt-1 text-sm text-slate-600">Search reservations and review their payment state.</p></div>
            <span class="text-sm font-semibold text-slate-500">{{ number_format($bookings->total()) }} records</span>
        </header>

        @if (session('status'))<p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950"><p class="font-bold">The booking action could not be completed.</p>@foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div>@endif

        <form method="GET" class="mt-6 grid gap-3 border-b border-slate-200 pb-6 sm:grid-cols-2 lg:grid-cols-[minmax(240px,1fr)_190px_170px_auto_auto]">
            <label class="sr-only" for="booking-search">Search bookings</label><input id="booking-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Reference, traveler name or email" class="min-h-11 rounded border border-slate-300 px-3 text-sm">
            <label class="sr-only" for="booking-status">Booking status</label><select id="booking-status" name="status" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm"><option value="">All statuses</option>@foreach(\App\BookingStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>@endforeach</select>
            <label class="sr-only" for="booking-type">Booking type</label><select id="booking-type" name="type" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm"><option value="">All types</option>@foreach(\App\BookingType::cases() as $type)<option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ str($type->value)->title() }}</option>@endforeach</select>
            <button class="min-h-11 rounded border border-slate-300 px-4 text-sm font-bold text-slate-800 hover:bg-slate-50">Filter</button><a href="{{ route('admin.bookings.index') }}" class="grid min-h-11 place-items-center text-sm font-semibold text-slate-600">Clear</a>
        </form>

        <div class="mt-2 overflow-x-auto"><table class="w-full min-w-[1000px] text-left text-sm">
            <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500"><tr><th class="py-3 pr-4">Reference</th><th class="py-3 pr-4">Traveler</th><th class="py-3 pr-4">Reservation</th><th class="py-3 pr-4">Travel dates</th><th class="py-3 pr-4">Total</th><th class="py-3 pr-4">Status</th><th class="py-3 pr-4">Payment</th><th class="py-3">Details</th></tr></thead>
            <tbody>@forelse($bookings as $booking)
                @php($payment = $booking->payments->sortByDesc('created_at')->first())
                <tr class="border-b border-slate-100 align-top">
                    <td class="py-4 pr-4"><a href="{{ route('admin.bookings.show', $booking) }}" class="font-bold text-emerald-900 hover:underline">{{ $booking->reference }}</a><p class="mt-1 text-[11px] text-slate-500">{{ str($booking->type->value)->title() }}</p></td>
                    <td class="py-4 pr-4"><p class="font-semibold text-slate-900">{{ $booking->traveler?->name ?? 'Unknown traveler' }}</p><p class="mt-1 text-xs text-slate-500">{{ $booking->traveler?->email }}</p></td>
                    <td class="py-4 pr-4">{{ $booking->trip?->name ?? $booking->vehicle?->name ?? 'Item unavailable' }}<p class="mt-1 text-xs text-slate-500">{{ $booking->travelers }} traveler(s)</p></td>
                    <td class="whitespace-nowrap py-4 pr-4">{{ $booking->start_date?->format('M j, Y') }}<p class="mt-1 text-xs text-slate-500">to {{ $booking->end_date?->format('M j, Y') }}</p></td>
                    <td class="whitespace-nowrap py-4 pr-4 font-semibold">{{ $booking->currency }} {{ number_format($booking->total_amount) }}</td>
                    <td class="py-4 pr-4">{{ str($booking->status->value)->replace('_', ' ')->title() }}</td>
                    <td class="py-4 pr-4">{{ $payment ? str($payment->status->value)->replace('_', ' ')->title() : 'No payment' }}</td>
                    <td class="py-4"><a href="{{ route('admin.bookings.show', $booking) }}" class="font-bold text-emerald-800 hover:underline">Open</a></td>
                </tr>
            @empty<tr><td colspan="8" class="py-10 text-center text-sm text-slate-600">No bookings match these filters.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="mt-6">{{ $bookings->links() }}</div>
    </div>
@endsection