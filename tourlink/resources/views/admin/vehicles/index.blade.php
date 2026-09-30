@extends('layouts.admin')

@section('title', 'Manage vehicles | TourLink')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6">
            <div><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Administration</p><h1 class="mt-2 text-3xl font-black text-slate-950">Manage vehicles</h1><p class="mt-2 text-sm text-slate-600">Edit fleet details, ownership, images and marketplace status.</p></div>
            <a href="{{ route('admin.vehicles.create') }}" class="rounded bg-emerald-800 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-900">Add vehicle</a>
        </header>

        @if (session('status'))<p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950"><p class="font-bold">The vehicle change could not be saved.</p>@foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div>@endif

        <form method="GET" class="mt-6 grid gap-3 border-b border-slate-200 pb-6 sm:grid-cols-2 lg:grid-cols-[minmax(220px,1fr)_180px_200px_auto_auto]">
            <label class="sr-only" for="vehicle-search">Search vehicles</label><input id="vehicle-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, registration or slug" class="min-h-11 rounded border border-slate-300 px-3 text-sm">
            <label class="sr-only" for="vehicle-status">Listing status</label><select id="vehicle-status" name="status" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm"><option value="">All listing statuses</option>@foreach (\App\ListingStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ str($status->value)->title() }}</option>@endforeach</select>
            <label class="sr-only" for="vehicle-review">Verification status</label><select id="vehicle-review" name="verification_status" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm"><option value="">All review statuses</option>@foreach (\App\VerificationStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['verification_status'] ?? '') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>@endforeach</select>
            <button class="min-h-11 rounded border border-slate-300 px-4 text-sm font-bold text-slate-800">Filter</button><a href="{{ route('admin.vehicles.index') }}" class="grid min-h-11 place-items-center text-sm font-semibold text-slate-600">Clear</a>
        </form>

        <div class="mt-2 overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500"><tr><th class="py-3 pr-4">Vehicle</th><th class="py-3 pr-4">Owner</th><th class="py-3 pr-4">Destination</th><th class="py-3 pr-4">Rate</th><th class="py-3 pr-4">Bookings</th><th class="py-3 pr-4">Status</th><th class="py-3">Actions</th></tr></thead>
            <tbody>@forelse ($vehicles as $vehicle)
                <tr class="border-b border-slate-100 align-top">
                    <td class="py-4 pr-4"><a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="font-bold text-slate-950 hover:underline">{{ $vehicle->name }}</a><p class="mt-1 text-xs text-slate-500">{{ $vehicle->registration_number }} · {{ $vehicle->make }} {{ $vehicle->model }}</p></td>
                    <td class="py-4 pr-4">{{ $vehicle->owner?->name ?? 'Unavailable' }}</td><td class="py-4 pr-4">{{ $vehicle->destination?->name ?? $vehicle->location }}</td>
                    <td class="whitespace-nowrap py-4 pr-4">KES {{ number_format($vehicle->price_per_day) }} / day</td><td class="py-4 pr-4">{{ $vehicle->bookings_count }}</td>
                    <td class="py-4 pr-4">{{ str($vehicle->status->value)->title() }}<p class="mt-1 text-xs text-slate-500">{{ str($vehicle->verification_status->value)->replace('_', ' ')->title() }}</p></td>
                    <td class="py-4"><div class="flex items-center gap-3"><a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="font-bold text-emerald-800 hover:underline">Edit</a>@if ($vehicle->status !== \App\ListingStatus::Archived)<form method="POST" action="{{ route('admin.vehicles.archive', $vehicle) }}" onsubmit="return confirm('Archive this vehicle? Booking history will be preserved.')">@csrf @method('DELETE')<button class="font-bold text-red-700 hover:underline">Archive</button></form>@endif</div></td>
                </tr>
            @empty<tr><td colspan="7" class="py-10 text-center text-sm text-slate-600">No vehicles match these filters.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="mt-6">{{ $vehicles->links() }}</div>
    </div>
@endsection