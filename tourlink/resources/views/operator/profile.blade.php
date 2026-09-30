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
    <form method="POST" action="{{ route('operator.verification.store') }}" class="grid gap-4 rounded border border-amber-200 bg-amber-50 p-6">@csrf<h2 class="font-black">Request verification</h2><p class="text-sm text-amber-900">Verification unlocks publishing and builds traveler trust.</p><textarea name="notes" rows="3" placeholder="Tell our team about your licenses, experience, and business documents" class="rounded border border-amber-300 bg-white p-3"></textarea><button class="w-fit rounded bg-amber-800 px-5 py-3 text-sm font-bold text-white">Submit verification request</button></form>
</div>
@endsection
