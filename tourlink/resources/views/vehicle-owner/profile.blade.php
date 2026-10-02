@extends('layouts.vehicle-owner')
@section('title', 'Vehicle-owner profile | TourLink')
@section('content')
<div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-8">
    <header>
        <p class="text-xs font-black uppercase tracking-widest text-emerald-800">Owner profile</p>
        <h1 class="mt-2 text-3xl font-black">Build trust with travelers</h1>
    </header>

    @if (session('status'))
        <p class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-950" role="alert">
            <p class="font-bold">We couldn't submit the verification documents.</p>
            @foreach ($errors->all() as $error)
                <p class="mt-1">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('vehicle-owner.profile.update') }}" class="grid gap-4 rounded border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <h2 class="text-lg font-black">Owner information</h2>
        <label class="grid gap-2 text-sm font-semibold">Business name<input required name="business_name" type="text" autocomplete="organization" autocapitalize="words" value="{{ old('business_name', $profile->business_name) }}" class="min-h-12 w-full rounded-xl border border-slate-300 px-3"></label>
        <label class="grid gap-2 text-sm font-semibold">About your business<textarea name="description" rows="4" class="w-full rounded-xl border border-slate-300 p-3">{{ old('description', $profile->description) }}</textarea></label>
        <button type="submit" class="min-h-12 w-full rounded-xl bg-ink px-6 text-sm font-bold text-white shadow-soft sm:w-fit">Save profile</button>
    </form>

    @php($identityDocuments = collect($identityVerificationRequest->documents ?? [])->pluck('key')->all())
    <section class="grid gap-4 rounded border border-amber-200 bg-amber-50 p-5 sm:p-6" aria-labelledby="owner-verification-heading">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-amber-900">Identity verification</p>
            <h2 id="owner-verification-heading" class="mt-1 text-xl font-black text-slate-950">Verify the vehicle owner</h2>
            @if ($identityVerificationRequest->exists)
                <p class="mt-2 text-sm font-bold text-amber-950">Status: {{ $identityVerificationRequest->status->label() }}</p>
            @endif
        </div>
        @if ($identityDocuments !== [])
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($identityVerificationRequest->documents as $document)
                    <li class="rounded border border-amber-200 bg-white px-3 py-2 text-sm"><strong>{{ $document['label'] }}</strong><span class="mt-1 block truncate text-xs text-slate-600">{{ $document['original_name'] }}</span></li>
                @endforeach
            </ul>
        @endif
        <form method="POST" action="{{ route('vehicle-owner.verification.identity.store') }}" enctype="multipart/form-data" class="grid gap-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([['owner_id', 'National ID or passport', true], ['driving_license', 'Driving licence, if you or your assigned driver will drive', false]] as [$key, $label, $required])
                    @php($hasDocument = in_array($key, $identityDocuments, true))
                    <label class="grid content-start gap-2 rounded border border-amber-200 bg-white p-3 text-sm font-bold text-slate-800" for="owner-document-{{ $key }}">
                        <span>{{ $label }}@if ($required)<span class="text-red-700" aria-hidden="true"> *</span>@endif</span>
                        <span class="text-xs font-normal leading-5 text-slate-600">PDF, JPG, or PNG. Maximum 10 MB.@if ($hasDocument) Leave empty to keep the current file.@endif</span>
                        <input id="owner-document-{{ $key }}" type="file" name="documents[{{ $key }}]" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="min-h-11 w-full text-xs file:mr-2 file:min-h-10 file:rounded file:border-0 file:bg-amber-800 file:px-3 file:font-bold file:text-white" @if ($required && ! $hasDocument) required @endif>
                    </label>
                @endforeach
            </div>
            <button type="submit" class="min-h-11 w-full rounded bg-amber-800 px-5 py-3 text-sm font-bold text-white hover:bg-amber-900 sm:w-fit">Submit owner identity</button>
        </form>
    </section>

    <section class="grid gap-5" aria-labelledby="vehicles-verification-heading">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-amber-900">Vehicle verification</p>
            <h2 id="vehicles-verification-heading" class="mt-1 text-xl font-black text-slate-950">Verify each vehicle</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Vehicle details come from your listing. Submit a separate document packet for each vehicle.</p>
        </div>

        @forelse ($vehicles as $vehicle)
            @php
                $verificationRequest = $vehicleVerificationRequests->get($vehicle->id);
                $uploadedDocuments = collect($verificationRequest?->documents ?? [])->pluck('key')->all();
                $vehicleDocuments = [
                    ['key' => 'logbook', 'label' => 'Vehicle logbook', 'required' => true, 'image' => false],
                    ['key' => 'insurance', 'label' => 'Current insurance certificate', 'required' => true, 'image' => false],
                    ['key' => 'inspection', 'label' => 'Inspection certificate, where applicable', 'required' => false, 'image' => false],
                    ['key' => 'front_photo', 'label' => 'Front photo', 'required' => true, 'image' => true],
                    ['key' => 'rear_photo', 'label' => 'Rear photo', 'required' => true, 'image' => true],
                    ['key' => 'left_photo', 'label' => 'Left-side photo', 'required' => true, 'image' => true],
                    ['key' => 'right_photo', 'label' => 'Right-side photo', 'required' => true, 'image' => true],
                    ['key' => 'interior_photo', 'label' => 'Interior photo', 'required' => true, 'image' => true],
                    ['key' => 'number_plate_photo', 'label' => 'Number-plate photo', 'required' => true, 'image' => true],
                ];
            @endphp
            <article class="grid gap-4 rounded border border-slate-200 bg-white p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">{{ $vehicle->registration_number }} · {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $vehicle->body_type }} · {{ $vehicle->seating_capacity }} seats</p>
                    </div>
                    @if ($verificationRequest)
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-950">{{ $verificationRequest->status->label() }}</span>
                    @endif
                </div>

                @if ($uploadedDocuments !== [])
                    <ul class="grid gap-2 sm:grid-cols-2">
                        @foreach ($verificationRequest->documents as $document)
                            <li class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm"><strong>{{ $document['label'] }}</strong><span class="mt-1 block truncate text-xs text-slate-600">{{ $document['original_name'] }}</span></li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('vehicle-owner.verification.vehicle.store') }}" enctype="multipart/form-data" class="grid gap-4">
                    @csrf
                    <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($vehicleDocuments as $document)
                            @php($hasDocument = in_array($document['key'], $uploadedDocuments, true))
                            <label class="grid content-start gap-2 rounded border border-slate-200 bg-slate-50 p-3 text-sm font-bold text-slate-800" for="vehicle-{{ $vehicle->id }}-{{ $document['key'] }}">
                                <span>{{ $document['label'] }}@if ($document['required'])<span class="text-red-700" aria-hidden="true"> *</span>@endif</span>
                                <span class="text-xs font-normal leading-5 text-slate-600">{{ $document['image'] ? 'JPG or PNG image, maximum 5 MB.' : 'PDF, JPG, or PNG, maximum 10 MB.' }}@if ($hasDocument) Leave empty to keep the current file.@endif</span>
                                <input id="vehicle-{{ $vehicle->id }}-{{ $document['key'] }}" type="file" name="documents[{{ $document['key'] }}]" accept="{{ $document['image'] ? 'image/jpeg,image/png' : '.pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png' }}" class="min-h-11 w-full text-xs file:mr-2 file:min-h-10 file:rounded file:border-0 file:bg-amber-800 file:px-3 file:font-bold file:text-white" @if ($document['required'] && ! $hasDocument) required @endif>
                            </label>
                        @endforeach
                    </div>
                    <label class="grid gap-2 text-sm font-semibold text-slate-800" for="vehicle-{{ $vehicle->id }}-verification-notes">Note for the reviewer <span class="text-xs font-normal text-slate-600">Optional</span><textarea id="vehicle-{{ $vehicle->id }}-verification-notes" name="notes" rows="2" maxlength="2000" class="rounded border border-slate-300 p-3">{{ old('notes') }}</textarea></label>
                    <button type="submit" class="min-h-11 w-full rounded bg-amber-800 px-5 py-3 text-sm font-bold text-white hover:bg-amber-900 sm:w-fit">Submit {{ $vehicle->registration_number }} for verification</button>
                </form>
            </article>
        @empty
            <div class="rounded border border-dashed border-slate-300 bg-white p-6">
                <p class="text-sm text-slate-700">Add a vehicle before submitting its registration details and documents.</p>
                <a class="mt-4 inline-flex min-h-11 items-center rounded bg-emerald-800 px-5 py-3 text-sm font-bold text-white" href="{{ route('vehicle-owner.vehicles.create') }}">Add your first vehicle</a>
            </div>
        @endforelse
    </section>
</div>
@endsection
