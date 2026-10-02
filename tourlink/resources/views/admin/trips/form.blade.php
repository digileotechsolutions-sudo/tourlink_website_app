@extends('layouts.admin')

@php
    $editing = $trip->exists;
    $textValue = fn (string $key, array $fallback): string => is_array(old($key, $fallback))
        ? implode("\n", old($key, $fallback))
        : (string) old($key, implode("\n", $fallback));
    $jsonValue = function (string $key, mixed $fallback): string {
        $value = old($key, $fallback);

        return is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    };
    $departure = old('departure_date', $trip->departure_date?->format('Y-m-d\TH:i') ?? '');
    $return = old('return_date', $trip->return_date?->format('Y-m-d\TH:i') ?? '');
@endphp

@section('title', ($editing ? 'Edit trip' : 'Create trip').' | Havenedge Tourlink')

@section('content')
    <div class="mx-auto max-w-5xl px-5 py-10 sm:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <a href="{{ route('admin.trips.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">Back to trips</a>
                <h1 class="mt-3 text-3xl font-black text-slate-950">{{ $editing ? 'Edit trip' : 'Create trip' }}</h1>
            </div>
            @if ($editing)
                <span class="text-sm text-slate-600">{{ $trip->bookings()->count() }} bookings</span>
            @endif
        </header>

        @if (session('status'))
            <p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950">
                <p class="font-bold">Review the highlighted fields.</p>
                @foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ $editing ? route('admin.trips.save', $trip) : route('admin.trips.store') }}" class="mt-8 space-y-10">
            @csrf
            @if ($editing) @method('PUT') @endif

            <section class="grid gap-5 sm:grid-cols-2" aria-labelledby="trip-basics-heading">
                <h2 id="trip-basics-heading" class="col-span-full border-b border-slate-200 pb-2 text-xl font-extrabold text-slate-950">Trip details</h2>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Trip name
                    <input name="name" value="{{ old('name', $trip->name) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Slug
                    <input name="slug" value="{{ old('slug', $trip->slug) }}" maxlength="255" placeholder="Generated from trip name when blank" class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Destination
                    <select name="destination_id" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal text-slate-950">
                        <option value="">Select destination</option>
                        @foreach ($destinations as $destination)
                            <option value="{{ $destination->id }}" @selected(old('destination_id', $trip->destination_id) === $destination->id)>{{ $destination->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Category
                    <select name="category_id" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal text-slate-950">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $trip->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Starting point
                    <input name="starting_point" value="{{ old('starting_point', $trip->starting_point) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Ending point
                    <input name="ending_point" value="{{ old('ending_point', $trip->ending_point) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700 sm:col-span-2">Description
                    <textarea name="description" required rows="5" maxlength="20000" class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ old('description', $trip->description) }}</textarea>
                </label>
            </section>

            <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" aria-labelledby="trip-schedule-heading">
                <h2 id="trip-schedule-heading" class="col-span-full border-b border-slate-200 pb-2 text-xl font-extrabold text-slate-950">Dates, price and capacity</h2>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Duration in days
                    <input type="number" name="duration_days" value="{{ old('duration_days', $trip->duration_days ?? 1) }}" min="1" max="365" required class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Departure date
                    <input type="datetime-local" name="departure_date" value="{{ $departure }}" required class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Return date
                    <input type="datetime-local" name="return_date" value="{{ $return }}" required class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Price per person (KES)
                    <input type="number" name="price_per_person" value="{{ old('price_per_person', $trip->price_per_person ?? 0) }}" min="0" step="1" required class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Maximum travelers
                    <input type="number" name="max_travelers" value="{{ old('max_travelers', $trip->max_travelers ?? 1) }}" min="1" required class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Minimum travelers
                    <input type="number" name="min_travelers" value="{{ old('min_travelers', $trip->min_travelers ?? 1) }}" min="1" required class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Available seats
                    <input type="number" name="available_seats" value="{{ old('available_seats', $trip->available_seats ?? 1) }}" min="0" required class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
            </section>

            <section class="grid gap-5 sm:grid-cols-2" aria-labelledby="trip-experience-heading">
                <h2 id="trip-experience-heading" class="col-span-full border-b border-slate-200 pb-2 text-xl font-extrabold text-slate-950">Experience and itinerary</h2>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Pickup points <span class="font-normal text-slate-500">One point per line</span>
                    <textarea name="pickup_points" rows="4" required class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ $textValue('pickup_points', $trip->pickup_points ?? []) }}</textarea>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Itinerary <span class="font-normal text-slate-500">JSON array of day, title and detail</span>
                    <textarea name="itinerary" rows="8" required class="rounded border border-slate-300 px-3 py-2 font-mono text-xs font-normal text-slate-950">{{ $jsonValue('itinerary', $trip->itinerary ?? [['day' => 1, 'title' => '', 'detail' => '']]) }}</textarea>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Accommodation
                    <textarea name="accommodation" rows="3" maxlength="5000" class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ old('accommodation', $trip->accommodation) }}</textarea>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Transport
                    <textarea name="transport" rows="3" maxlength="5000" class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ old('transport', $trip->transport) }}</textarea>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Meals <span class="font-normal text-slate-500">One item per line</span>
                    <textarea name="meals" rows="4" class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ $textValue('meals', $trip->meals ?? []) }}</textarea>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Activities <span class="font-normal text-slate-500">One item per line</span>
                    <textarea name="activities" rows="4" class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ $textValue('activities', $trip->activities ?? []) }}</textarea>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Included items <span class="font-normal text-slate-500">One item per line</span>
                    <textarea name="included_items" rows="4" class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ $textValue('included_items', $trip->included_items ?? []) }}</textarea>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Excluded items <span class="font-normal text-slate-500">One item per line</span>
                    <textarea name="excluded_items" rows="4" class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ $textValue('excluded_items', $trip->excluded_items ?? []) }}</textarea>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700 sm:col-span-2">Cancellation policy
                    <textarea name="cancellation_policy" required rows="4" maxlength="10000" class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ old('cancellation_policy', $trip->cancellation_policy) }}</textarea>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700 sm:col-span-2">Trip images <span class="font-normal text-slate-500">JSON array of URL and optional alt text; up to 12</span>
                    <textarea name="images" rows="7" class="rounded border border-slate-300 px-3 py-2 font-mono text-xs font-normal text-slate-950">{{ $jsonValue('images', $trip->images->map(fn ($image): array => ['url' => $image->url, 'alt' => $image->alt])->all()) }}</textarea>
                </label>
            </section>

            <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" aria-labelledby="trip-ownership-heading">
                <h2 id="trip-ownership-heading" class="col-span-full border-b border-slate-200 pb-2 text-xl font-extrabold text-slate-950">Operator and publication</h2>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Tour operator
                    <select name="operator_id" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal text-slate-950">
                        <option value="">Select approved operator</option>
                        @foreach ($operators as $operator)
                            <option value="{{ $operator->id }}" @selected(old('operator_id', $trip->operator_id) === $operator->id)>{{ $operator->name }} · {{ $operator->email }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Required vehicle
                    <select name="required_vehicle_id" class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal text-slate-950">
                        <option value="">No required vehicle</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected(old('required_vehicle_id', $trip->required_vehicle_id) === $vehicle->id)>{{ $vehicle->name }} · {{ $vehicle->owner?->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Listing status
                    <select name="status" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal text-slate-950">
                        @foreach ($listingStatuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $trip->status?->value ?? 'DRAFT') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Verification status
                    <select name="verification_status" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal text-slate-950">
                        @foreach ($verificationStatuses as $status)
                            <option value="{{ $status->value }}" @selected(old('verification_status', $trip->verification_status?->value ?? 'PENDING') === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex min-h-11 items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="featured" value="1" @checked(old('featured', $trip->featured ?? false)) class="size-4 accent-emerald-800">
                    Featured listing
                </label>
            </section>

            <footer class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route('admin.trips.index') }}" class="rounded border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button class="rounded bg-emerald-800 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-900">{{ $editing ? 'Save trip' : 'Create trip' }}</button>
            </footer>
        </form>
    </div>
@endsection