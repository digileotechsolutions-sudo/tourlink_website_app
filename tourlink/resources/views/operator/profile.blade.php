@extends('layouts.operator')
@section('title', 'Company profile | TourLink')
@section('content')
<div class="mx-auto max-w-3xl space-y-6 p-4 sm:p-8">
    <div><p class="text-xs font-black uppercase tracking-widest text-emerald-800">Company profile</p><h1 class="mt-2 text-3xl font-black">Present your business</h1></div>
    <form method="POST" action="{{ route('operator.profile.update') }}" enctype="multipart/form-data" class="grid gap-4 rounded border border-slate-200 bg-white p-6 sm:grid-cols-2">
        @csrf @method('PUT')
        <label class="grid gap-1 text-sm font-semibold">Company name<input required name="company_name" value="{{ old('company_name', $profile->company_name) }}" class="min-h-11 rounded border border-slate-300 px-3"></label>
        <label class="grid gap-1 text-sm font-semibold">Public slug<input required name="slug" value="{{ old('slug', $profile->slug) }}" class="min-h-11 rounded border border-slate-300 px-3"></label>
        <label class="grid gap-1 text-sm font-semibold sm:col-span-2">Business description<textarea name="description" rows="5" class="rounded border border-slate-300 p-3">{{ old('description', $profile->description) }}</textarea></label>
        <label class="grid gap-1 text-sm font-semibold">Logo URL<input type="url" name="logo_url" value="{{ old('logo_url', $profile->logo_url) }}" class="min-h-11 rounded border border-slate-300 px-3"></label>
        <label class="grid gap-1 text-sm font-semibold">Upload logo<input type="file" name="logo" accept="image/*" class="rounded border border-slate-300 p-2"></label>
        <label class="grid gap-1 text-sm font-semibold">Website<input type="url" name="website" value="{{ old('website', $profile->website) }}" class="min-h-11 rounded border border-slate-300 px-3"></label>
        <label class="grid gap-1 text-sm font-semibold">Years active<input type="number" min="0" name="years_active" value="{{ old('years_active', $profile->years_active) }}" class="min-h-11 rounded border border-slate-300 px-3"></label>
        <div class="sm:col-span-2"><button class="rounded bg-emerald-800 px-6 py-3 text-sm font-bold text-white">Save company profile</button></div>
    </form>
    @php
        $operatorDocuments = collect(\App\OperatorVerificationDocument::cases())->map(fn ($document): array => [
            'key' => $document->value,
            'label' => $document->label(),
            'guidance' => $document->guidance(),
            'required' => $document->required(),
        ]);
        $uploadedOperatorDocuments = collect($verificationRequest->documents ?? [])->pluck('key')->all();
    @endphp
    <section class="grid gap-5 rounded border border-amber-200 bg-amber-50 p-5 sm:p-6" aria-labelledby="operator-verification-heading">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-amber-900">Business verification</p>
            <h2 id="operator-verification-heading" class="mt-1 text-xl font-black text-slate-950">Submit your documents</h2>
            <p class="mt-2 text-sm leading-6 text-amber-950">Upload clear, current copies. Documents are private and only available to TourLink reviewers.</p>
        </div>

        @if (session('status'))
            <p class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-950" role="alert">
                <p class="font-bold">We couldn't submit the verification request.</p>
                @foreach ($errors->all() as $error)
                    <p class="mt-1">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($verificationRequest->exists)
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="font-semibold text-slate-700">Status:</span>
                <span class="rounded-full bg-white px-3 py-1 font-bold text-amber-900">{{ $verificationRequest->status->label() }}</span>
                @if ($verificationRequest->status === \App\VerificationStatus::Approved)
                    <span class="font-bold text-emerald-800" aria-label="Verified">Verified</span>
                @endif
            </div>
        @endif

        @if ($uploadedOperatorDocuments !== [])
            <ul class="grid gap-2 sm:grid-cols-2" aria-label="Previously uploaded documents">
                @foreach ($verificationRequest->documents as $document)
                    <li class="rounded border border-amber-200 bg-white px-3 py-2 text-sm">
                        <span class="font-bold text-slate-800">{{ $document['label'] ?? str($document['key'] ?? 'Document')->headline() }}</span>
                        <span class="mt-1 block truncate text-xs text-slate-600">{{ $document['original_name'] ?? 'Uploaded' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('operator.verification.store') }}" enctype="multipart/form-data" class="grid gap-4">
            @csrf
            <label class="grid gap-2 rounded border border-amber-200 bg-white p-3 text-sm font-bold text-slate-800" for="company-profile">
                Company/business profile <span class="text-red-700" aria-hidden="true">*</span>
                <span class="text-xs font-normal leading-5 text-slate-600">Briefly describe your tour company, services offered, destinations served, and years of operation.</span>
                <textarea id="company-profile" name="company_profile" rows="5" minlength="20" maxlength="5000" required class="rounded border border-slate-300 p-3 font-normal">{{ old('company_profile', $profile->description) }}</textarea>
                @error('company_profile')
                    <span class="text-xs font-semibold text-red-700">{{ $message }}</span>
                @enderror
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($operatorDocuments as $document)
                    @php($errorKey = 'documents.'.$document['key'])
                    <label class="grid content-start gap-2 rounded border border-amber-200 bg-white p-3 text-sm font-bold text-slate-800" for="document-{{ $document['key'] }}">
                        <span>{{ $document['label'] }} @if ($document['required'])<span class="text-red-700" aria-hidden="true">*</span>@else<span class="font-normal text-slate-500">(optional)</span>@endif</span>
                        <span class="text-xs font-normal leading-5 text-slate-600">{{ $document['guidance'] }} PDF, JPG, or PNG; maximum 10 MB.</span>
                        <input id="document-{{ $document['key'] }}" type="file" name="documents[{{ $document['key'] }}]" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="min-h-11 w-full text-xs file:mr-2 file:min-h-10 file:rounded file:border-0 file:bg-amber-800 file:px-3 file:font-bold file:text-white" @if ($document['required'] && ! in_array($document['key'], $uploadedOperatorDocuments, true)) required @endif @if ($errors->has($errorKey)) aria-invalid="true" aria-describedby="{{ $document['key'] }}-error" @endif>
                        @error($errorKey)
                            <span id="{{ $document['key'] }}-error" class="text-xs font-semibold text-red-700">{{ $message }}</span>
                        @enderror
                    </label>
                @endforeach
            </div>

            <label class="grid gap-2 text-sm font-semibold text-slate-800" for="verification-notes">
                Note for the reviewer <span class="text-xs font-normal text-slate-600">Optional</span>
                <textarea id="verification-notes" name="notes" rows="3" maxlength="2000" placeholder="Add context for your application" class="rounded border border-amber-300 bg-white p-3">{{ old('notes', $verificationRequest->notes) }}</textarea>
            </label>
            <button type="submit" class="min-h-11 w-full rounded bg-amber-800 px-5 py-3 text-sm font-bold text-white hover:bg-amber-900 sm:w-fit">Submit for verification</button>
        </form>
    </section>
</div>
@endsection
