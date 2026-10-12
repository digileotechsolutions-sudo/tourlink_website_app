@extends('layouts.admin')

@php
    $editing = $trip->exists;
    $textValue = fn (string $key, array $fallback): string => is_array(old($key, $fallback))
        ? implode("\n", old($key, $fallback))
        : (string) old($key, implode("\n", $fallback));
    $departure = old('departure_date', $trip->departure_date?->format('Y-m-d\TH:i') ?? '');
    $return = old('return_date', $trip->return_date?->format('Y-m-d\TH:i') ?? '');
    $itineraryInput = old('itinerary', $trip->itinerary ?? [['day' => 1, 'title' => '', 'detail' => '']]);
    $itinerary = is_array($itineraryInput) ? $itineraryInput : [['day' => 1, 'title' => '', 'detail' => '']];
    $additionalCharges = old('additional_charges', $trip->additional_charges ?: [['name' => '', 'amount' => '']]);
@endphp

@section('title', ($editing ? 'Edit trip' : 'Create trip').' | Havenedge Tourlink')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-3 py-4 sm:px-6 lg:px-8">
        <header class="mb-3 flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.trips.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">Back to trips</a>
                <h1 class="mt-1 text-xl font-black text-slate-950">{{ $editing ? 'Edit trip' : 'Create trip' }}</h1>
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

        <form data-admin-trip-wizard data-admin-step-names="Details|Itinerary|Pricing|Media|Publish" data-admin-invalid-fields="{{ implode(',', $errors->keys()) }}" method="POST" action="{{ $editing ? route('admin.trips.save', $trip) : route('admin.trips.store') }}" enctype="multipart/form-data" novalidate class="admin-trip-wizard overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @if ($editing) @method('PUT') @endif
            <input type="hidden" name="wizard_step" value="{{ old('wizard_step', 0) }}" data-admin-wizard-current>

            <header class="bg-ink px-4 pb-3 pt-4 text-white">
                <div class="flex items-center gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white/15" aria-hidden="true">HT</span>
                    <div>
                        <h2 class="text-base font-bold">{{ $editing ? 'Edit trip' : 'Publish a new trip' }}</h2>
                        <p class="mt-0.5 text-[11px] text-white/80">Administration · Trip management</p>
                    </div>
                </div>
                <p class="mt-2 flex items-center gap-2 text-[10px] font-semibold"><span data-admin-wizard-count class="rounded-full bg-white/15 px-2 py-1">Step 1 of 5</span><span data-admin-wizard-name>Details</span></p>
            </header>

            <nav class="px-3 pt-3 sm:px-5" aria-label="Trip form progress">
                <ol class="grid grid-cols-5 gap-1">
                    @foreach (['Details', 'Itinerary', 'Pricing', 'Media', 'Publish'] as $index => $label)
                        <li><button type="button" data-admin-wizard-link="{{ $index }}" class="block w-full text-left text-[9px] font-medium text-slate-700" aria-current="{{ $index === 0 ? 'step' : 'false' }}"><span data-admin-progress-segment class="mb-1.5 block h-[3px] rounded-full bg-slate-200"></span>{{ $index + 1 }}. {{ $label }}</button></li>
                    @endforeach
                </ol>
            </nav>

            <section data-admin-trip-step="0" class="grid gap-4 px-3 py-4 sm:grid-cols-2 sm:px-5" aria-labelledby="trip-basics-heading">
                <h2 id="trip-basics-heading" class="col-span-full text-lg font-black">Trip information</h2>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Trip name
                    <input name="name" value="{{ old('name', $trip->name) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                    <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Slug
                    <input name="slug" value="{{ old('slug', $trip->slug) }}" maxlength="255" placeholder="Generated from trip name when blank" class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Destination
                    <input name="destination_name" value="{{ old('destination_name', $trip->destination?->name) }}" required maxlength="255" placeholder="Enter destination name" class="min-h-11 rounded border border-slate-300 px-3 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Category
                    <select name="category_id" required class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal text-slate-950">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $trip->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700 sm:col-span-2">Description
                    <textarea name="description" required rows="5" maxlength="20000" class="rounded border border-slate-300 px-3 py-2 font-normal text-slate-950">{{ old('description', $trip->description) }}</textarea>
                </label>
            </section>

            <section data-admin-trip-step="1" class="grid gap-4 px-3 py-4 sm:grid-cols-2 lg:grid-cols-3 sm:px-5" aria-labelledby="trip-schedule-heading" hidden>
                <h2 id="trip-schedule-heading" class="col-span-full text-lg font-black">Itinerary and schedule</h2>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Starting point
                    <input name="starting_point" value="{{ old('starting_point', $trip->starting_point) }}" required maxlength="255" class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Ending point
                    <input name="ending_point" value="{{ old('ending_point', $trip->ending_point) }}" required maxlength="255" class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Duration in days
                    <input type="number" name="duration_days" value="{{ old('duration_days', $trip->duration_days ?? 1) }}" min="1" max="365" required class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Departure date
                    <input type="datetime-local" name="departure_date" value="{{ $departure }}" required class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Return date
                    <input type="datetime-local" name="return_date" value="{{ $return }}" required class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Meeting point<input name="meeting_point" value="{{ old('meeting_point', $trip->meeting_point) }}" maxlength="255" placeholder="e.g. Nairobi CBD" class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950"></label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Transport type<select name="transport_type" class="min-h-11 rounded border border-slate-300 bg-white px-3 font-normal"><option value="">Choose type</option>@foreach(['TOUR_VAN' => 'Tour van', 'BUS' => 'Bus', '4X4_SAFARI' => '4×4 safari vehicle', 'PRIVATE_CAR' => 'Private car', 'NO_TRANSPORT' => 'No transport'] as $value => $label)<option value="{{ $value }}" @selected(old('transport_type', $trip->transport_type) === $value)>{{ $label }}</option>@endforeach</select></label>
            </section>

            <section data-admin-trip-step="2" class="grid gap-4 px-3 py-4 sm:grid-cols-2 sm:px-5" aria-labelledby="trip-pricing-heading" hidden>
                <h2 id="trip-pricing-heading" class="col-span-full text-lg font-black">Pricing, capacity and itinerary details</h2>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Price per person (KES)
                    <input type="number" name="price_per_person" value="{{ old('price_per_person', $trip->price_per_person ?? 0) }}" min="0" step="1" required class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Child price (KES)<input type="number" name="child_price" value="{{ old('child_price', $trip->child_price) }}" min="0" step="1" class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950"></label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Deposit (KES)<input type="number" name="deposit_amount" value="{{ old('deposit_amount', $trip->deposit_amount) }}" min="0" step="1" class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950"></label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Group discount (%)<input type="number" name="group_discount_percent" value="{{ old('group_discount_percent', $trip->group_discount_percent) }}" min="0" max="100" class="min-h-11 rounded-full border border-slate-300 px-3 font-normal text-slate-950"></label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Maximum travelers
                    <input type="number" name="max_travelers" value="{{ old('max_travelers', $trip->max_travelers ?? 1) }}" min="1" required class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Minimum travelers
                    <input type="number" name="min_travelers" value="{{ old('min_travelers', $trip->min_travelers ?? 1) }}" min="1" required class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Available seats
                    <input type="number" name="available_seats" value="{{ old('available_seats', $trip->available_seats ?? 1) }}" min="0" required class="min-h-11 rounded-full border border-slate-300 px-4 font-normal text-slate-950">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Pickup points <span class="font-normal text-slate-500">One per line</span><textarea name="pickup_points" rows="3" required class="rounded-xl border border-slate-300 px-4 py-2 font-normal text-slate-950">{{ $textValue('pickup_points', $trip->pickup_points ?? []) }}</textarea></label>
                <div class="grid gap-3 sm:col-span-2"><div class="flex items-center justify-between"><div><p class="text-sm font-semibold">Additional charges (KES)</p><p class="text-xs text-slate-500">Optional fees to disclose on the trip listing.</p></div><button type="button" data-admin-add-charge class="min-h-9 rounded border border-emerald-800 px-3 text-xs font-bold text-emerald-900">＋ Add charge</button></div><div data-admin-charges class="grid gap-2">@foreach($additionalCharges as $index => $charge)<div class="grid gap-2 rounded border border-slate-200 p-3 sm:grid-cols-[1fr_180px_auto]"><label class="grid gap-1 text-xs font-semibold">Charge name<input name="additional_charges[{{ $index }}][name]" value="{{ $charge['name'] ?? '' }}" placeholder="e.g. Park fees" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-semibold">Amount<input type="number" min="0" name="additional_charges[{{ $index }}][amount]" value="{{ $charge['amount'] ?? '' }}" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label><button type="button" data-admin-remove-charge class="self-end rounded border px-3 py-2 text-xs text-red-700">Remove</button></div>@endforeach</div></div>
                <div class="grid gap-3 sm:col-span-2" data-admin-itinerary>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><p class="text-sm font-semibold text-slate-700">Day-by-day itinerary</p><p class="text-xs text-slate-500">Add each day's title, time, and activity details.</p></div>
                        <button type="button" data-admin-add-day class="min-h-10 rounded border border-emerald-800 px-4 text-xs font-bold text-emerald-900 hover:bg-emerald-50">＋ Add a day</button>
                    </div>
                    <div class="grid gap-3" data-admin-itinerary-rows>
                        @foreach($itinerary as $index => $day)
                            <article class="rounded border border-slate-200 bg-slate-50 p-4" data-admin-itinerary-day>
                                <div class="mb-3 flex items-center justify-between"><p class="text-sm font-bold">Day <span data-admin-day-number>{{ $day['day'] ?? $index + 1 }}</span></p><button type="button" data-admin-remove-day class="text-xs font-bold text-red-700">Remove day</button></div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="grid gap-1 text-xs font-bold text-slate-700">Day number<input type="number" min="1" required name="itinerary[{{ $index }}][day]" value="{{ $day['day'] ?? $index + 1 }}" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-bold text-slate-700">Title<input maxlength="120" required name="itinerary[{{ $index }}][title]" value="{{ $day['title'] ?? '' }}" placeholder="e.g. Arrival and welcome" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-bold text-slate-700">Start time<input type="time" name="itinerary[{{ $index }}][start_time]" value="{{ $day['start_time'] ?? '' }}" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-bold text-slate-700">End time<input type="time" name="itinerary[{{ $index }}][end_time]" value="{{ $day['end_time'] ?? '' }}" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-bold text-slate-700">Activity location<input maxlength="255" name="itinerary[{{ $index }}][location]" value="{{ $day['location'] ?? '' }}" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-bold text-slate-700">Meals<input maxlength="500" name="itinerary[{{ $index }}][meals]" value="{{ $day['meals'] ?? '' }}" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-bold text-slate-700 sm:col-span-2">Activities and details<textarea maxlength="4000" required rows="2" name="itinerary[{{ $index }}][detail]" placeholder="Describe the day's activities and schedule" class="rounded border border-slate-300 px-3 py-2 text-sm font-normal">{{ $day['detail'] ?? '' }}</textarea></label>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
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
            </section>

            <section data-admin-trip-step="3" class="grid gap-4 px-3 py-4 sm:grid-cols-2 sm:px-5" aria-labelledby="trip-media-heading" hidden>
                <h2 id="trip-media-heading" class="col-span-full text-lg font-black">Trip media</h2>
                <div class="grid gap-3 sm:col-span-2" data-admin-image-upload>
                    <div><p class="text-sm font-semibold text-slate-700">Cover and gallery images</p><p class="mt-1 text-xs text-slate-500">Drag photos here or choose files. JPG, PNG, or WebP, up to 5 MB each, 6 MB combined, and 12 images total. The first image is the cover.</p></div>
                    <label data-admin-dropzone class="grid min-h-32 cursor-pointer place-items-center rounded-xl border-2 border-dashed border-emerald-300 bg-emerald-50/50 p-5 text-center hover:bg-emerald-50"><input data-admin-image-input type="file" name="images_files[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only"><span><span class="mx-auto grid size-10 place-items-center rounded-full bg-white text-xl font-bold text-emerald-800 shadow-sm" aria-hidden="true">＋</span><span class="mt-2 block text-sm font-bold text-emerald-950">Drop images here or browse</span><span class="mt-1 block text-xs text-slate-500">Add one cover photo and optional gallery images</span></span></label>
                    <div data-admin-image-preview class="grid grid-cols-2 gap-3 sm:grid-cols-4">@foreach($trip->images as $image)<div data-admin-existing-image class="relative overflow-hidden rounded-lg border border-slate-200"><img src="{{ $image->url }}" alt="{{ $image->alt ?: $trip->name }}" class="h-24 w-full object-cover"><input type="hidden" name="images[{{ $loop->index }}][url]" value="{{ $image->url }}"><input type="hidden" name="images[{{ $loop->index }}][alt]" value="{{ $image->alt }}"><button type="button" data-admin-remove-image class="absolute right-2 top-2 grid size-7 place-items-center rounded-full bg-white font-bold text-red-700 shadow" aria-label="Remove image">×</button>@if($loop->first)<span class="absolute bottom-1 left-1 rounded bg-white/95 px-2 py-1 text-[9px] font-bold uppercase">Cover</span>@endif</div>@endforeach</div>
                    <p data-admin-image-status class="text-xs text-slate-600" aria-live="polite"></p>
                </div>
            </section>

            <section data-admin-trip-step="4" class="grid gap-4 px-3 py-4 sm:grid-cols-2 lg:grid-cols-3 sm:px-5" aria-labelledby="trip-ownership-heading" hidden>
                <h2 id="trip-ownership-heading" class="col-span-full text-lg font-black">Publish settings</h2>
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
                <label class="flex min-h-11 items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="featured" value="1" @checked(old('featured', $trip->featured ?? false)) class="size-4 accent-emerald-800">
                    Featured listing
                </label>
            </section>

            <footer class="flex flex-wrap gap-2 border-t border-slate-100 bg-white px-3 py-3 sm:px-5">
                <button type="button" data-admin-wizard-back class="min-h-11 min-w-0 flex-1 rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-600 disabled:opacity-40">Back</button>
                <a data-admin-wizard-cancel href="{{ route('admin.trips.index') }}" class="hidden min-h-10 flex-1 items-center justify-center rounded-full border border-slate-300 px-4 py-2 text-center text-xs font-medium text-slate-600 hover:bg-slate-50">Cancel</a>
                <button type="button" data-admin-wizard-next class="min-h-11 min-w-0 flex-1 rounded-full bg-ink px-5 text-sm font-bold text-white hover:opacity-90">Continue <span aria-hidden="true">→</span></button>
                <button type="submit" data-admin-wizard-submit class="hidden min-h-11 min-w-0 flex-1 rounded-full bg-ink px-5 text-sm font-bold text-white hover:opacity-90">{{ $editing ? 'Save trip' : 'Create trip' }}</button>
            </footer>
        </form>
    </div>
@endsection
