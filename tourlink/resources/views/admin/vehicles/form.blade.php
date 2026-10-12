@extends('layouts.admin')

@php
    $editing = $vehicle->exists;
    $charges = old('additional_charges', $vehicle->additional_charges ?: [['name' => '', 'amount' => '']]);
    $serviceAreas = old('service_areas', $vehicle->service_areas ?: []);
@endphp

@section('title', ($editing ? 'Edit vehicle' : 'Add vehicle').' | Havenedge Tourlink')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-3 py-5 sm:px-6 lg:px-8">
        <header class="border-b border-slate-200 pb-6"><a href="{{ route('admin.vehicles.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">Back to vehicles</a><h1 class="mt-3 text-3xl font-black text-slate-950">{{ $editing ? 'Edit vehicle' : 'Add vehicle' }}</h1>@if($editing)<p class="mt-2 text-sm text-slate-600">{{ $vehicle->bookings()->count() }} bookings · history is retained when archived</p>@endif</header>
        @if (session('status'))<p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950"><p class="font-bold">Review the highlighted fields.</p>@foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div>@endif

        <form method="POST" action="{{ $editing ? route('admin.vehicles.save', $vehicle) : route('admin.vehicles.store') }}" enctype="multipart/form-data" class="mt-8 space-y-9">
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
                <label class="grid gap-1 text-sm font-semibold">Hourly rate (KES)<input type="number" name="hourly_price" value="{{ old('hourly_price', $vehicle->hourly_price) }}" min="0" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Trip rate (KES)<input type="number" name="trip_price" value="{{ old('trip_price', $vehicle->trip_price) }}" min="0" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Driver charge (KES)<input type="number" name="driver_charge" value="{{ old('driver_charge', $vehicle->driver_charge) }}" min="0" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Location<input name="location" value="{{ old('location', $vehicle->location) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold">Vehicle owner<select name="owner_id" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal"><option value="">Select approved owner</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected(old('owner_id', $vehicle->owner_id) === $owner->id)>{{ $owner->name }} · {{ $owner->email }}</option>@endforeach</select></label>
                <label class="grid gap-1 text-sm font-semibold">Destination<select name="destination_id" class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal"><option value="">No destination</option>@foreach($destinations as $destination)<option value="{{ $destination->id }}" @selected(old('destination_id', $vehicle->destination_id) === $destination->id)>{{ $destination->name }}</option>@endforeach</select></label>
                <label class="grid gap-1 text-sm font-semibold sm:col-span-2 lg:col-span-3">Service areas <span class="font-normal text-slate-500">One city or area per line</span><textarea name="service_areas" rows="2" class="rounded border border-slate-300 px-3 py-2 font-normal">{{ is_array($serviceAreas) ? implode("\n", $serviceAreas) : $serviceAreas }}</textarea></label>
                <div class="grid gap-3 sm:col-span-2 lg:col-span-3" data-admin-vehicle-charges>
                    <div class="flex items-center justify-between"><div><p class="text-sm font-semibold">Additional charges (KES)</p><p class="text-xs text-slate-500">Optional customer-facing fees.</p></div><button type="button" data-admin-vehicle-add-charge class="min-h-10 rounded border border-emerald-800 px-3 text-xs font-bold text-emerald-900">＋ Add fee</button></div>
                    <div data-admin-vehicle-charge-rows class="grid gap-2">
                        @foreach($charges as $index => $charge)
                            <div data-admin-vehicle-charge-row class="grid gap-2 rounded border border-slate-200 p-3 sm:grid-cols-[1fr_180px_auto]"><label class="grid gap-1 text-xs font-semibold">Fee name<input name="additional_charges[{{ $index }}][name]" value="{{ $charge['name'] ?? '' }}" placeholder="e.g. Cleaning fee" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-semibold">Amount<input type="number" min="0" name="additional_charges[{{ $index }}][amount]" value="{{ $charge['amount'] ?? '' }}" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label><button type="button" data-admin-vehicle-remove-charge class="self-end rounded border px-3 py-2 text-xs text-red-700">Remove</button></div>
                        @endforeach
                    </div>
                </div>
                <fieldset class="flex flex-wrap gap-x-5 gap-y-3 text-sm font-semibold text-slate-700 sm:col-span-2 lg:col-span-3"><legend class="mb-2">Features</legend><label class="flex items-center gap-2"><input type="checkbox" name="air_conditioning" value="1" @checked(old('air_conditioning', $vehicle->air_conditioning ?? false)) class="size-4 accent-emerald-800">Air conditioning</label><label class="flex items-center gap-2"><input type="checkbox" name="four_by_four" value="1" @checked(old('four_by_four', $vehicle->four_by_four ?? false)) class="size-4 accent-emerald-800">4×4</label><label class="flex items-center gap-2"><input type="checkbox" name="driver_included" value="1" @checked(old('driver_included', $vehicle->driver_included ?? false)) class="size-4 accent-emerald-800">Driver included</label></fieldset>
                <div class="grid gap-3 sm:col-span-2 lg:col-span-3" data-admin-image-upload>
                    <div><p class="text-sm font-semibold">Vehicle images</p><p class="mt-1 text-xs text-slate-500">Drag images here or choose files. JPG, JPEG, PNG, or WebP, max {{ number_format(config('services.tourlink.vehicle_image_max_kb', 5120) / 1024, 1) }} MB each and {{ number_format(config('services.tourlink.vehicle_image_total_max_kb', 6144) / 1024, 1) }} MB combined, up to 12 images.</p></div>
                    <label data-admin-dropzone class="grid min-h-32 cursor-pointer place-items-center rounded-xl border-2 border-dashed border-emerald-300 bg-emerald-50/50 p-5 text-center hover:bg-emerald-50">
                        <input data-admin-image-input type="file" name="images_files[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
                        <span><span class="mx-auto grid size-10 place-items-center rounded-full bg-white text-xl font-bold text-emerald-800 shadow-sm" aria-hidden="true">＋</span><span class="mt-2 block text-sm font-bold text-emerald-950">Drop images here or browse</span><span class="mt-1 block text-xs text-slate-500">Add up to 12 vehicle photos</span></span>
                    </label>
                    <div data-admin-image-preview class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach($vehicle->images as $image)
                            <div data-admin-existing-image class="relative overflow-hidden rounded-lg border border-slate-200">
                                <img src="{{ $image->url }}" alt="{{ $image->alt ?: $vehicle->name }}" class="h-24 w-full object-cover">
                                <input type="hidden" name="images[{{ $loop->index }}][url]" value="{{ $image->url }}">
                                <input type="hidden" name="images[{{ $loop->index }}][alt]" value="{{ $image->alt }}">
                                <button type="button" data-admin-remove-image class="absolute right-2 top-2 grid size-7 place-items-center rounded-full bg-white font-bold text-red-700 shadow" aria-label="Remove image">×</button>
                            </div>
                        @endforeach
                    </div>
                    <p data-admin-image-status class="text-xs text-slate-600" aria-live="polite"></p>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2" aria-labelledby="vehicle-status-heading">
                <h2 id="vehicle-status-heading" class="col-span-full border-b border-slate-200 pb-2 text-xl font-extrabold text-slate-950">Publication</h2>
                <label class="grid gap-1 text-sm font-semibold">Listing status<select name="status" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal">@foreach($listingStatuses as $status)<option value="{{ $status->value }}" @selected(old('status', $vehicle->status?->value ?? 'DRAFT') === $status->value)>{{ str($status->value)->title() }}</option>@endforeach</select></label>
            </section>

            <footer class="flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('admin.vehicles.index') }}" class="rounded border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700">Cancel</a><button class="rounded bg-emerald-800 px-6 py-3 text-sm font-bold text-white">{{ $editing ? 'Save vehicle' : 'Add vehicle' }}</button></footer>
        </form>
    </div>
@endsection
