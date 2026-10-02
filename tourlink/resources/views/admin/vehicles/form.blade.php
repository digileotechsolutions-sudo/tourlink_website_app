@extends('layouts.admin')

@php
    $editing = $vehicle->exists;
    $imageValue = old('images', $vehicle->images->map(fn ($image): array => ['url' => $image->url, 'alt' => $image->alt])->all());
    $imagesJson = is_string($imageValue) ? $imageValue : json_encode($imageValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

@section('title', ($editing ? 'Edit vehicle' : 'Add vehicle').' | Havenedge Tourlink')

@section('content')
    <div class="mx-auto max-w-5xl px-5 py-10 sm:px-8">
        <header class="border-b border-slate-200 pb-6"><a href="{{ route('admin.vehicles.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">Back to vehicles</a><h1 class="mt-3 text-3xl font-black text-slate-950">{{ $editing ? 'Edit vehicle' : 'Add vehicle' }}</h1>@if($editing)<p class="mt-2 text-sm text-slate-600">{{ $vehicle->bookings()->count() }} bookings · history is retained when archived</p>@endif</header>
        @if (session('status'))<p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950"><p class="font-bold">Review the highlighted fields.</p>@foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div>@endif

        <form method="POST" action="{{ $editing ? route('admin.vehicles.save', $vehicle) : route('admin.vehicles.store') }}" class="mt-8 space-y-9">
            @csrf @if($editing) @method('PUT') @endif
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-labelledby="vehicle-identity-heading">
                <h2 id="vehicle-identity-heading" class="col-span-full border-b border-slate-200 pb-2 text-xl font-extrabold text-slate-950">Vehicle identity</h2>
                <label class="grid gap-1 text-sm font-semibold">Name<input name="name" value="{{ old('name', $vehicle->name) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Slug <span class="font-normal text-slate-500">Generated when blank</span><input name="slug" value="{{ old('slug', $vehicle->slug) }}" maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Registration<input name="registration_number" value="{{ old('registration_number', $vehicle->registration_number) }}" required maxlength="30" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Make<input name="make" value="{{ old('make', $vehicle->make) }}" required maxlength="120" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Model<input name="model" value="{{ old('model', $vehicle->model) }}" required maxlength="120" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Year<input type="number" name="year" value="{{ old('year', $vehicle->year ?? now()->year) }}" min="1900" max="{{ now()->year + 2 }}" required class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Body type<input name="body_type" value="{{ old('body_type', $vehicle->body_type) }}" required class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Colour<input name="colour" value="{{ old('colour', $vehicle->colour) }}" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Engine CC<input type="number" name="engine_cc" value="{{ old('engine_cc', $vehicle->engine_cc) }}" min="0" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Seating capacity<input type="number" name="seating_capacity" value="{{ old('seating_capacity', $vehicle->seating_capacity ?? 1) }}" min="1" max="100" required class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Tare weight<input type="number" name="tare_weight" value="{{ old('tare_weight', $vehicle->tare_weight) }}" min="0" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Axles<input type="number" name="axles" value="{{ old('axles', $vehicle->axles) }}" min="1" max="10" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Load capacity<input type="number" name="load_capacity" value="{{ old('load_capacity', $vehicle->load_capacity) }}" min="0" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-labelledby="vehicle-service-heading">
                <h2 id="vehicle-service-heading" class="col-span-full border-b border-slate-200 pb-2 text-xl font-extrabold text-slate-950">Service and location</h2>
                <label class="grid gap-1 text-sm font-semibold">Transmission<input name="transmission" value="{{ old('transmission', $vehicle->transmission) }}" required class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Fuel type<input name="fuel_type" value="{{ old('fuel_type', $vehicle->fuel_type) }}" required class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Price per day (KES)<input type="number" name="price_per_day" value="{{ old('price_per_day', $vehicle->price_per_day ?? 0) }}" min="0" required class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Location<input name="location" value="{{ old('location', $vehicle->location) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Vehicle owner<select name="owner_id" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal"><option value="">Select approved owner</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected(old('owner_id', $vehicle->owner_id) === $owner->id)>{{ $owner->name }} · {{ $owner->email }}</option>@endforeach</select></label>
                <label class="grid gap-1 text-sm font-semibold">Destination<select name="destination_id" class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal"><option value="">No destination</option>@foreach($destinations as $destination)<option value="{{ $destination->id }}" @selected(old('destination_id', $vehicle->destination_id) === $destination->id)>{{ $destination->name }}</option>@endforeach</select></label>
                <fieldset class="flex flex-wrap gap-x-5 gap-y-3 text-sm font-semibold text-slate-700 sm:col-span-2 lg:col-span-3"><legend class="mb-2">Features</legend><label class="flex items-center gap-2"><input type="checkbox" name="air_conditioning" value="1" @checked(old('air_conditioning', $vehicle->air_conditioning ?? false)) class="size-4 accent-emerald-800">Air conditioning</label><label class="flex items-center gap-2"><input type="checkbox" name="four_by_four" value="1" @checked(old('four_by_four', $vehicle->four_by_four ?? false)) class="size-4 accent-emerald-800">4×4</label><label class="flex items-center gap-2"><input type="checkbox" name="driver_included" value="1" @checked(old('driver_included', $vehicle->driver_included ?? false)) class="size-4 accent-emerald-800">Driver included</label></fieldset>
                <label class="grid gap-1 text-sm font-semibold sm:col-span-2 lg:col-span-3">Vehicle images <span class="font-normal text-slate-500">JSON array of URL and optional alt text; up to 12</span><textarea name="images" rows="6" class="rounded border border-slate-300 px-3 py-2 font-mono text-xs font-normal">{{ $imagesJson }}</textarea></label>
            </section>

            <section class="grid gap-4 sm:grid-cols-2" aria-labelledby="vehicle-status-heading">
                <h2 id="vehicle-status-heading" class="col-span-full border-b border-slate-200 pb-2 text-xl font-extrabold text-slate-950">Publication</h2>
                <label class="grid gap-1 text-sm font-semibold">Listing status<select name="status" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal">@foreach($listingStatuses as $status)<option value="{{ $status->value }}" @selected(old('status', $vehicle->status?->value ?? 'DRAFT') === $status->value)>{{ str($status->value)->title() }}</option>@endforeach</select></label>
                <label class="grid gap-1 text-sm font-semibold">Verification status<select name="verification_status" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal">@foreach($verificationStatuses as $status)<option value="{{ $status->value }}" @selected(old('verification_status', $vehicle->verification_status?->value ?? 'PENDING') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>@endforeach</select></label>
            </section>

            <footer class="flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('admin.vehicles.index') }}" class="rounded border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700">Cancel</a><button class="rounded bg-emerald-800 px-6 py-3 text-sm font-bold text-white">{{ $editing ? 'Save vehicle' : 'Add vehicle' }}</button></footer>
        </form>
    </div>
@endsection