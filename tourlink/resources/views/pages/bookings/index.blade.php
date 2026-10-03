@extends('layouts.app')
@section('title', __('My bookings').' | Havenedge Tourlink')
@section('content')
<section class="bg-ink py-12 text-white"><div class="container-page"><p class="eyebrow text-sun">{{ __('Your journeys') }}</p><h1 class="display mt-2 text-4xl font-bold sm:text-6xl">{{ __('My bookings') }}</h1><p class="mt-4 max-w-xl text-sm text-white/70">{{ __('Keep every request, payment and trip detail in one place.') }}</p></div></section>
<section class="container-page py-10">
    @if(session('status'))<div class="mb-5 rounded-xl bg-leaf/10 p-4 text-sm font-bold text-leaf">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-5 rounded-xl bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
    <div class="grid gap-4">
        @forelse($bookings as $booking)
            <article class="rounded-2xl bg-white p-5 shadow-soft sm:flex sm:items-center sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $booking->reference }} · {{ __($booking->status->value) }}</p><h2 class="mt-2 text-lg font-black text-ink">{{ $booking->trip?->name ?? $booking->vehicle?->name ?? __('Journey') }}</h2><p class="mt-1 text-sm text-slate-500">{{ $booking->start_date->locale(app()->getLocale())->translatedFormat('M j, Y') }} {{ __('to') }} {{ $booking->end_date->locale(app()->getLocale())->translatedFormat('M j, Y') }} · KES {{ number_format($booking->total_amount) }}</p></div><div class="mt-4 flex items-center gap-3 sm:mt-0"><span class="inline-flex rounded-full bg-sand px-3 py-2 text-xs font-extrabold text-ink">{{ __($booking->payments->first()?->status?->value ?? 'PENDING') }}</span><a class="rounded-lg bg-ink px-4 py-2.5 text-xs font-extrabold text-white hover:bg-emerald-900" href="{{ route('payments.show', $booking) }}">{{ __('View / pay') }}</a></div></article>
        @empty
            <div class="rounded-2xl bg-white p-10 text-center shadow-soft"><h2 class="text-xl font-black">{{ __('No bookings yet') }}</h2><p class="mt-2 text-sm text-slate-500">{{ __('Find a trip or vehicle and start your next journey.') }}</p></div>
        @endforelse
    </div>
    <div class="mt-7">{{ $bookings->links() }}</div>
</section>
@endsection
