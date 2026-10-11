@extends('layouts.operator')
@section('title', ($trip->exists ? 'Edit trip' : 'Create a trip').' | Havenedge Tourlink')
@php
    $operator = auth()->user();
    $profile = $operator->operatorProfile;
    $itineraryInput = old('itinerary', $trip->itinerary ?: [['day' => 1, 'title' => '', 'detail' => '']]);
    $itinerary = is_array($itineraryInput) ? $itineraryInput : [['day' => 1, 'title' => '', 'detail' => '']];
    $stops = old('pickup_points', $trip->pickup_points ?: ['']);
    $listValues = function (string $key, array $fallback): array {
        $value = old($key, $fallback);

        if (is_string($value)) {
            $value = preg_split('/\R/u', $value) ?: [];
        }

        return is_array($value) ? $value : $fallback;
    };
    $highlights = $listValues('highlights', $trip->highlights ?: []);
    $includedItems = $listValues('included_items', $trip->included_items ?: []);
    $excludedItems = $listValues('excluded_items', $trip->excluded_items ?: []);
    $meals = $listValues('meals', $trip->meals ?: []);
    $formDate = fn (mixed $value): string => filled($value) ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : '';
    $formTime = fn (mixed $value, string $fallback): string => filled($value) ? \Illuminate\Support\Carbon::parse($value)->format('H:i') : $fallback;
    $departureDateInput = old('departure_date', $trip->departure_date);
    $returnDateInput = old('return_date', $trip->return_date);
    $returnTimeInput = old('return_time', $formTime($returnDateInput, '18:00'));
    $meetingPointAndTime = old('meeting_point_and_time', trim(($trip->meeting_point ?? '').($trip->meeting_point && $trip->departure_date ? ', ' : '').($trip->departure_date?->format('g:i A') ?? '')));
    $additionalCharges = old('additional_charges', $trip->additional_charges ?: [['name' => '', 'amount' => '']]);
    $transportTypes = [
        'TOUR_VAN' => 'Tour van',
        'BUS' => 'Bus',
        '4X4_SAFARI' => '4×4 safari vehicle',
        'PRIVATE_CAR' => 'Private car',
        'NO_TRANSPORT' => 'No transport',
    ];
@endphp
@section('content')
<div class="mx-auto max-w-[640px] space-y-4 px-3 py-4 pb-28 sm:px-6 sm:py-8">
    <form id="trip-wizard" data-trip-wizard method="POST" action="{{ $trip->exists ? route('operator.trips.update', $trip) : route('operator.trips.store') }}" enctype="multipart/form-data" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @csrf
        @if($trip->exists) @method('PUT') @endif

        <header class="bg-emerald-800 px-4 py-4 text-white sm:px-6">
            <div class="flex items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white/15" aria-hidden="true"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="7" width="16" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M9 12h6M12 10v4" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div><h1 class="text-lg font-black">{{ $trip->exists ? 'Edit your trip' : 'Publish a new trip' }}</h1><p class="mt-0.5 text-xs text-emerald-50">Operator dashboard · Trip management</p></div></div>
            <p data-trip-caption class="mt-3 flex items-center gap-2 text-xs font-semibold"><span data-trip-caption-count class="rounded-full bg-white/15 px-2 py-1">Step 1 of 4</span><span data-trip-caption-name>Trip information</span></p>
        </header>

        <nav class="px-3 pt-3 sm:px-5" aria-label="Trip form progress">
            <ol class="grid grid-cols-4 gap-1">
                @foreach(['Details', 'Schedule', 'Pricing', 'Publish'] as $index => $label)
                    <li><button type="button" data-trip-step-link="{{ $index }}" class="block w-full text-left text-[10px] font-medium text-slate-700" aria-current="{{ $index === 0 ? 'step' : 'false' }}"><span data-progress-segment class="mb-1.5 block h-[3px] rounded-full bg-slate-200"></span>{{ $index + 1 }}. {{ $label }}</button></li>
                @endforeach
            </ol>
        </nav>

        <section data-trip-step="0" class="space-y-5 px-3 py-4 sm:px-5" aria-labelledby="trip-step-title-0">
            <div><h2 id="trip-step-title-0" class="text-lg font-black">Route and schedule</h2></div>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="grid gap-1.5 text-xs font-semibold sm:col-span-2">Departure location <span class="text-red-700">*</span><input required name="starting_point" maxlength="255" value="{{ old('starting_point', $trip->starting_point ?: 'Nairobi') }}" placeholder="Nairobi" class="min-h-10 rounded-full border border-slate-300 px-4 text-sm font-normal"></label>
                <label class="grid gap-1.5 text-xs font-semibold sm:col-span-2">Destination <span class="text-red-700">*</span><input required maxlength="255" name="destination_name" data-trip-destination value="{{ old('destination_name', $trip->destination?->name) }}" placeholder="e.g. Diani Beach, Kwale" class="min-h-10 rounded-full border border-slate-300 px-4 text-sm font-normal"><input type="hidden" name="ending_point" data-trip-ending value="{{ old('ending_point', $trip->ending_point ?: $trip->destination?->name) }}"></label>
                <label class="grid gap-1.5 text-xs font-semibold">Departure date <span class="text-red-700">*</span><input required type="date" name="departure_date" value="{{ $formDate($departureDateInput) }}" class="min-h-10 rounded-full border border-slate-300 px-4 text-sm font-normal"></label>
                <label class="grid gap-1.5 text-xs font-semibold">Return date<input required type="date" name="return_date" value="{{ $formDate($returnDateInput) }}" class="min-h-10 rounded-full border border-slate-300 px-4 text-sm font-normal"></label>
                <fieldset class="grid gap-2 sm:col-span-2"><legend class="text-xs font-semibold">Trip duration</legend><div class="grid gap-2">@foreach([1 => '1 day', 2 => '2 days', 3 => '3–5 days', 6 => '6 or more days'] as $days => $label)<label class="flex min-h-7 items-center gap-2 text-sm"><input type="radio" name="duration_days" value="{{ $days }}" required @checked((int) old('duration_days', $trip->duration_days ?: 2) === $days) class="size-4 accent-emerald-800">{{ $label }}</label>@endforeach</div></fieldset>
                <label class="grid gap-1.5 text-xs font-semibold sm:col-span-2">Meeting point and departure time<input name="meeting_point_and_time" maxlength="255" value="{{ $meetingPointAndTime }}" placeholder="e.g. Nairobi CBD, 6:00 AM" class="min-h-10 rounded-full border border-slate-300 px-4 text-sm font-normal"></label>
                <fieldset class="grid gap-2 sm:col-span-2"><legend class="text-xs font-semibold">Transport provided</legend><div class="grid gap-2">@foreach($transportTypes as $value => $label)<label class="flex min-h-7 items-center gap-2 text-sm"><input type="radio" name="transport_type" value="{{ $value }}" required @checked(old('transport_type', $trip->transport_type ?: 'TOUR_VAN') === $value) class="size-4 accent-emerald-800">{{ $label === 'No transport' ? 'No transport included' : $label }}</label>@endforeach</div></fieldset>
            </div>
        </section>

        <section data-trip-step="1" class="hidden space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-7" aria-labelledby="trip-step-title-1" hidden>
            <div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">02 · Schedule</p><h2 id="trip-step-title-1" class="mt-2 text-2xl font-black">Trip details and itinerary</h2><p class="mt-1 text-sm text-slate-600">Add the trip overview, photos, route stops, and day-by-day activities.</p></div>
            <label class="grid gap-2 text-sm font-bold">Trip title <span class="text-red-700">*</span><input required maxlength="255" name="name" value="{{ old('name', $trip->name) }}" placeholder="e.g. 3-day Amboseli wildlife escape" class="min-h-12 rounded-xl border border-slate-300 px-4 font-normal"></label>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-bold">Trip category <span class="text-red-700">*</span><select required name="category_id" class="min-h-12 rounded-xl border border-slate-300 bg-white px-4 font-normal"><option value="">Choose a category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $trip->category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label class="grid gap-2 text-sm font-bold">Description <span class="text-red-700">*</span><textarea required maxlength="20000" rows="4" name="description" placeholder="What makes this trip memorable? Tell travelers what to expect." class="rounded-xl border border-slate-300 p-4 font-normal">{{ old('description', $trip->description) }}</textarea></label>
                <div class="grid gap-3 sm:col-span-2">
                    <div><p class="text-sm font-bold">Cover and gallery images <span class="text-red-700">*</span></p><p class="mt-1 text-xs leading-5 text-slate-500">Choose up to 12 JPG, PNG, or WebP images (5 MB each). The first image is the trip cover.</p></div>
                    <label data-trip-dropzone class="grid min-h-28 cursor-pointer place-items-center rounded-2xl border-2 border-dashed border-emerald-200 bg-emerald-50/50 p-4 text-center hover:bg-emerald-50"><input data-trip-images class="sr-only" type="file" name="images_files[]" accept="image/jpeg,image/png,image/webp" multiple><span><span class="mx-auto grid size-9 place-items-center rounded-xl bg-white text-xl text-emerald-800 shadow-sm" aria-hidden="true">＋</span><span class="mt-2 block text-sm font-black text-emerald-950">Drop photos here or browse</span><span class="mt-1 block text-xs text-slate-600">Choose cover and gallery images</span></span></label>
                    <div data-trip-image-preview class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach($trip->images as $image)
                            <div data-existing-image class="relative overflow-hidden rounded-xl border border-slate-200"><img src="{{ $image->url }}" alt="{{ $image->alt ?: $trip->name }}" class="h-24 w-full object-cover"><input type="hidden" name="images[{{ $loop->index }}][url]" value="{{ $image->url }}"><input type="hidden" name="images[{{ $loop->index }}][alt]" value="{{ $image->alt }}"><button type="button" data-remove-image class="absolute right-2 top-2 grid size-8 place-items-center rounded-full bg-white font-black text-red-700 shadow" aria-label="Remove image">×</button>@if($loop->first)<span class="absolute bottom-1 left-1 rounded bg-white/95 px-2 py-1 text-[9px] font-black uppercase">Cover</span>@endif</div>
                        @endforeach
                    </div>
                    <p data-trip-upload-status class="text-xs text-slate-600" aria-live="polite"></p>
                </div>
                <label class="grid gap-2 text-sm font-bold sm:col-span-2">Highlights <span class="font-normal text-slate-500">One highlight per line</span><textarea name="highlights" rows="3" placeholder="Sunrise game drive&#10;Small-group local guide&#10;Picnic beside the river" class="rounded-xl border border-slate-300 p-4 font-normal">{{ implode("\n", $highlights) }}</textarea></label>
                <label class="grid gap-1 text-xs font-semibold">Return time <input required type="time" name="return_time" value="{{ $returnTimeInput }}" class="min-h-10 rounded-full border border-slate-300 px-4 text-sm font-normal"></label>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-3 sm:col-span-2"><div><p class="text-sm font-bold">Stops along the way <span class="font-normal text-slate-500">Optional</span></p><p class="mt-1 text-xs text-slate-500">Add any planned pickup or sightseeing stops in order.</p></div><div data-trip-repeater="stops" class="grid gap-3">@foreach($stops as $index => $stop)<div class="flex gap-2"><label class="sr-only" for="trip-stop-{{ $index }}">Stop {{ $index + 1 }}</label><input id="trip-stop-{{ $index }}" name="pickup_points[]" value="{{ $stop }}" placeholder="Stop or pickup point" class="min-h-11 min-w-0 flex-1 rounded-xl border border-slate-300 px-4"><button type="button" data-remove-row class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-bold text-red-700" aria-label="Remove stop">Remove</button></div>@endforeach</div><button type="button" data-add-row="stops" class="min-h-11 w-fit rounded-xl border border-emerald-700 px-4 text-sm font-bold text-emerald-900">＋ Add a stop</button></div>
                <div class="grid gap-3 sm:col-span-2"><div class="flex flex-wrap items-end justify-between gap-2"><div><p class="text-sm font-bold">Day-by-day itinerary <span class="text-red-700">*</span></p><p class="mt-1 text-xs text-slate-500">Add the activities and key details for every day.</p></div><button type="button" data-add-row="itinerary" class="min-h-10 rounded-xl border border-emerald-700 px-4 text-xs font-bold text-emerald-900">＋ Add a day</button></div><div data-trip-repeater="itinerary" class="grid gap-3">@foreach($itinerary as $index => $day)<article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="mb-3 flex items-center justify-between"><p class="text-sm font-black">Day <span data-day-label>{{ $day['day'] ?? $index + 1 }}</span></p><button type="button" data-remove-row class="text-xs font-bold text-red-700">Remove day</button></div><div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-1 text-xs font-bold">Day number<input required type="number" min="1" name="itinerary[{{ $index }}][day]" value="{{ $day['day'] ?? $index + 1 }}" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">Title<input required maxlength="120" name="itinerary[{{ $index }}][title]" value="{{ $day['title'] ?? '' }}" placeholder="e.g. Arrival and welcome" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">Start time<input type="time" name="itinerary[{{ $index }}][start_time]" value="{{ $day['start_time'] ?? '' }}" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">End time<input type="time" name="itinerary[{{ $index }}][end_time]" value="{{ $day['end_time'] ?? '' }}" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold sm:col-span-2">Activities & details<textarea required maxlength="4000" rows="2" name="itinerary[{{ $index }}][detail]" placeholder="Describe the day's schedule and activities" class="rounded-lg border border-slate-300 p-3 text-sm font-normal">{{ $day['detail'] ?? '' }}</textarea></label></div></article>@endforeach</div></div>
                <label class="grid gap-2 text-sm font-bold">Transport details <span class="font-normal text-slate-500">Optional</span><input name="transport" value="{{ old('transport', $trip->transport) }}" placeholder="e.g. Air-conditioned vehicle and guide" class="min-h-12 rounded-xl border border-slate-300 px-4 font-normal"></label>
                <label class="grid gap-2 text-sm font-bold sm:col-span-2">Accommodation <span class="font-normal text-slate-500">Optional</span><textarea name="accommodation" rows="2" placeholder="Include lodging details when relevant" class="rounded-xl border border-slate-300 p-4 font-normal">{{ old('accommodation', $trip->accommodation) }}</textarea></label>
            </div>
        </section>

        <section data-trip-step="2" class="hidden space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-7" aria-labelledby="trip-step-title-2" hidden>
            <div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">03 · Make it bookable</p><h2 id="trip-step-title-2" class="mt-2 text-2xl font-black">Pricing & capacity</h2><p class="mt-1 text-sm text-slate-600">Set clear prices, group sizes, and what is included.</p></div>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-bold">Adult price <span class="text-red-700">*</span><span class="relative"><span class="absolute left-4 top-3 text-sm font-bold text-slate-500">KES</span><input required type="number" min="0" max="100000000" step="1" name="price_per_person" value="{{ old('price_per_person', $trip->price_per_person) }}" placeholder="0" class="min-h-12 w-full rounded-xl border border-slate-300 pl-16 pr-4 font-normal"></span></label>
                <label class="grid gap-2 text-sm font-bold">Child price <span class="font-normal text-slate-500">Optional · KES</span><input type="number" min="0" max="100000000" name="child_price" value="{{ old('child_price', $trip->child_price) }}" placeholder="Same as adult if blank" class="min-h-12 rounded-xl border border-slate-300 px-4 font-normal"></label>
                <label class="grid gap-2 text-sm font-bold">Maximum travelers <span class="text-red-700">*</span><input required type="number" min="1" max="10000" name="max_travelers" value="{{ old('max_travelers', $trip->max_travelers ?: 1) }}" class="min-h-12 rounded-xl border border-slate-300 px-4 font-normal"></label>
                <label class="grid gap-2 text-sm font-bold">Available seats <span class="text-red-700">*</span><input required type="number" min="0" max="10000" name="available_seats" value="{{ old('available_seats', $trip->available_seats ?: 1) }}" class="min-h-12 rounded-xl border border-slate-300 px-4 font-normal"></label>
                <label class="grid gap-2 text-sm font-bold">Minimum group size <span class="text-red-700">*</span><input required type="number" min="1" max="10000" name="min_travelers" value="{{ old('min_travelers', $trip->min_travelers ?: 1) }}" class="min-h-12 rounded-xl border border-slate-300 px-4 font-normal"></label>
                <label class="grid gap-2 text-sm font-bold">Optional booking deposit <span class="font-normal text-slate-500">KES</span><input type="number" min="0" max="100000000" name="deposit_amount" value="{{ old('deposit_amount', $trip->deposit_amount) }}" placeholder="No deposit" class="min-h-12 rounded-xl border border-slate-300 px-4 font-normal"></label>
                <div class="grid gap-3 sm:col-span-2"><p class="text-sm font-bold">Additional charges <span class="font-normal text-slate-500">Optional</span></p><div data-trip-repeater="charges" class="grid gap-3">@foreach($additionalCharges as $index => $charge)<div class="grid gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-[1fr_180px_auto]"><label class="grid gap-1 text-xs font-bold">Charge name<input name="additional_charges[{{ $index }}][name]" value="{{ $charge['name'] ?? '' }}" placeholder="e.g. Park conservation fee" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><label class="grid gap-1 text-xs font-bold">Amount (KES)<input type="number" min="0" name="additional_charges[{{ $index }}][amount]" value="{{ $charge['amount'] ?? '' }}" placeholder="0" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-normal"></label><button type="button" data-remove-row class="min-h-10 self-end rounded-lg border border-slate-300 px-3 text-xs font-bold text-red-700">Remove</button></div>@endforeach</div><button type="button" data-add-row="charges" class="min-h-11 w-fit rounded-xl border border-emerald-700 px-4 text-sm font-bold text-emerald-900">＋ Add a charge</button></div>
                <label class="grid gap-2 text-sm font-bold">What's included <span class="font-normal text-slate-500">One per line</span><textarea name="included_items" rows="4" placeholder="Professional local guide&#10;Transport during the trip&#10;Park entry fees" class="rounded-xl border border-slate-300 p-4 font-normal">{{ implode("\n", $includedItems) }}</textarea></label>
                <label class="grid gap-2 text-sm font-bold">What's not included <span class="font-normal text-slate-500">One per line</span><textarea name="excluded_items" rows="4" placeholder="International flights&#10;Personal expenses" class="rounded-xl border border-slate-300 p-4 font-normal">{{ implode("\n", $excludedItems) }}</textarea></label>
                <label class="grid gap-2 text-sm font-bold">Cancellation & refund policy <span class="text-red-700">*</span><textarea required maxlength="10000" name="cancellation_policy" rows="4" placeholder="Explain when a traveler can cancel and what refund they can expect." class="rounded-xl border border-slate-300 p-4 font-normal">{{ old('cancellation_policy', $trip->cancellation_policy) }}</textarea></label>
                <label class="grid gap-2 text-sm font-bold">Meals <span class="font-normal text-slate-500">One per line</span><textarea name="meals" rows="4" placeholder="Breakfast&#10;Lunch" class="rounded-xl border border-slate-300 p-4 font-normal">{{ implode("\n", $meals) }}</textarea></label>
            </div>
        </section>

        <section data-trip-step="3" class="hidden space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-7" aria-labelledby="trip-step-title-3" hidden>
            <div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">04 · Final check</p><h2 id="trip-step-title-3" class="mt-2 text-2xl font-black">Review & publish</h2><p class="mt-1 text-sm text-slate-600">Review how your trip will appear before sending it to our team.</p></div>
            <article class="overflow-hidden rounded-2xl border border-slate-200">
                <div data-preview-cover class="grid min-h-48 place-items-center bg-emerald-50 text-sm font-bold text-emerald-900">Your trip cover photo will appear here</div>
                <div class="space-y-4 p-5 sm:p-7"><div><p data-preview-category class="text-xs font-black uppercase tracking-widest text-emerald-800">Trip category</p><h3 data-preview-title class="mt-2 text-2xl font-black">Your trip title</h3><p data-preview-destination class="mt-1 text-sm text-slate-600">Destination</p></div><p data-preview-description class="whitespace-pre-line text-sm leading-6 text-slate-700">Your trip description will appear here.</p><div class="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3"><p><span class="block text-xs font-bold uppercase text-slate-500">Route</span><span data-preview-route>Start → End</span></p><p><span class="block text-xs font-bold uppercase text-slate-500">Dates</span><span data-preview-dates>Departure → Return</span></p><p><span class="block text-xs font-bold uppercase text-slate-500">From</span><span data-preview-price>KES — per adult</span></p></div><div><p class="text-xs font-black uppercase tracking-wide text-slate-500">Organized by</p><p class="mt-1 font-bold">{{ $profile?->company_name ?: $operator->name }}</p><p class="text-sm text-slate-600">{{ $operator->email }}{{ $operator->phone ? ' · '.$operator->phone : '' }}</p><p class="mt-1 text-xs font-semibold text-emerald-900">Operator verification: {{ $operatorVerificationRequest->status?->label() ?? 'Not submitted' }} · <a class="underline" href="{{ route('operator.profile') }}">Manage profile</a></p></div></div>
            </article>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950"><strong>Approval process:</strong> saving a draft keeps the trip private. Submitting sends it to the Tourlink team for review. Once approved, you can publish it from your trip dashboard. Your account and operator ownership are verified on the server.</div>
            @if($vehicles->isNotEmpty())<label class="grid max-w-xl gap-2 text-sm font-bold">Associated vehicle <span class="font-normal text-slate-500">Optional</span><select name="required_vehicle_id" class="min-h-12 rounded-xl border border-slate-300 bg-white px-4 font-normal"><option value="">No specific vehicle</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected(old('required_vehicle_id', $trip->required_vehicle_id) === $vehicle->id)>{{ $vehicle->name }}</option>@endforeach</select></label>@endif
        </section>

        <div class="sticky bottom-20 z-10 flex gap-2 border-t border-slate-100 bg-white px-3 py-3 sm:bottom-4 sm:px-5">
            <button type="button" data-trip-back class="min-h-11 flex-1 rounded-full border border-slate-300 px-4 text-sm font-medium text-slate-600 disabled:opacity-40">Back</button>
            <button type="button" data-trip-next class="min-h-11 flex-1 rounded-full bg-neutral-950 px-5 text-sm font-bold text-white hover:bg-neutral-800">Continue <span aria-hidden="true">→</span></button>
            <button type="submit" formnovalidate name="submission_action" value="draft" data-trip-draft class="hidden min-h-11 rounded-full border border-emerald-800 px-4 text-sm font-bold text-emerald-900">Save draft</button>
            <button type="submit" formnovalidate name="submission_action" value="submit" data-trip-submit class="hidden min-h-11 flex-1 rounded-full bg-neutral-950 px-5 text-sm font-bold text-white">Submit for approval</button>
        </div>
    </form>

    @if($trip->exists)
        <form method="POST" action="{{ route('operator.trips.delete', $trip) }}" onsubmit="return confirm('Unpublish and archive this trip?')" class="text-right">@csrf @method('DELETE')<button class="text-sm font-bold text-red-700 underline underline-offset-2">Unpublish and archive trip</button></form>
    @endif
</div>
@endsection
