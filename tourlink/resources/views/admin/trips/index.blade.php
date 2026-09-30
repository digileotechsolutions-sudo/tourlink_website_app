@extends('layouts.admin')

@section('title', 'Manage trips | TourLink')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Administration</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950">Manage trips</h1>
                <p class="mt-2 text-sm text-slate-600">Create listings, edit trip details, and manage publication.</p>
            </div>
            <a href="{{ route('admin.trips.create') }}" class="rounded bg-emerald-800 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-900">Create trip</a>
        </header>

        @if (session('status'))
            <p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>
        @endif

        <form method="GET" class="mt-6 grid gap-3 border-b border-slate-200 pb-6 sm:grid-cols-2 lg:grid-cols-[minmax(220px,1fr)_180px_200px_auto_auto]">
            <label class="sr-only" for="trip-search">Search trips</label>
            <input id="trip-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search trips or slugs" class="min-h-11 rounded border border-slate-300 px-3 text-sm">
            <label class="sr-only" for="trip-status-filter">Listing status</label>
            <select id="trip-status-filter" name="status" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm">
                <option value="">All listing statuses</option>
                @foreach (\App\ListingStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="trip-verification-filter">Verification status</label>
            <select id="trip-verification-filter" name="verification_status" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm">
                <option value="">All review statuses</option>
                @foreach (\App\VerificationStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['verification_status'] ?? '') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <button class="min-h-11 rounded border border-slate-300 px-4 text-sm font-bold text-slate-800 hover:bg-slate-50">Filter</button>
            <a href="{{ route('admin.trips.index') }}" class="grid min-h-11 place-items-center text-sm font-semibold text-slate-600 hover:text-slate-950">Clear</a>
        </form>

        <div class="mt-2 overflow-x-auto">
            <table class="w-full min-w-[920px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500">
                    <tr>
                        <th class="py-3 pr-4">Trip</th>
                        <th class="py-3 pr-4">Operator</th>
                        <th class="py-3 pr-4">Destination</th>
                        <th class="py-3 pr-4">Price</th>
                        <th class="py-3 pr-4">Bookings</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4">Review</th>
                        <th class="py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trips as $trip)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="py-4 pr-4">
                                <a href="{{ route('admin.trips.edit', $trip) }}" class="font-bold text-slate-950 hover:underline">{{ $trip->name }}</a>
                                <p class="mt-1 text-xs text-slate-500">{{ $trip->slug }}@if($trip->featured) · Featured @endif</p>
                            </td>
                            <td class="py-4 pr-4">{{ $trip->operator?->name ?? 'Unavailable' }}</td>
                            <td class="py-4 pr-4">{{ $trip->destination?->name ?? 'Unavailable' }}<p class="mt-1 text-xs text-slate-500">{{ $trip->category?->name ?? 'No category' }}</p></td>
                            <td class="py-4 pr-4 whitespace-nowrap">KES {{ number_format($trip->price_per_person) }}</td>
                            <td class="py-4 pr-4">{{ $trip->bookings_count }}</td>
                            <td class="py-4 pr-4">{{ str($trip->status->value)->replace('_', ' ')->title() }}</td>
                            <td class="py-4 pr-4">{{ str($trip->verification_status->value)->replace('_', ' ')->title() }}</td>
                            <td class="py-4">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.trips.edit', $trip) }}" class="font-bold text-emerald-800 hover:underline">Edit</a>
                                    @if ($trip->status !== \App\ListingStatus::Archived)
                                        <form method="POST" action="{{ route('admin.trips.archive', $trip) }}" onsubmit="return confirm('Archive this trip? Its booking history will be preserved.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="font-bold text-red-700 hover:underline">Archive</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-sm text-slate-600">No trips match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $trips->links() }}</div>
    </div>
@endsection