@extends('layouts.traveler')
@section('title', 'Traveler profile | Havenedge Tourlink')
@section('content')
<div class="mx-auto max-w-3xl space-y-6 p-4 sm:p-8">
    <header>
        <p class="text-xs font-black uppercase tracking-widest text-ink">Profile</p>
        <h1 class="mt-2 text-3xl font-black">Tell operators about you</h1>
    </header>
    @if (session('status'))
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-950" role="alert">
            <p class="font-bold">Please check the details below.</p>
            @foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach
        </div>
    @endif
    <form method="POST" enctype="multipart/form-data" action="{{ route('traveler.profile.update') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-soft sm:grid-cols-2 sm:p-6">
        @csrf @method('PUT')
        <label class="grid gap-2 text-sm font-semibold" for="traveler-name">Name<input id="traveler-name" required name="name" type="text" autocomplete="name" autocapitalize="words" value="{{ old('name', auth()->user()->name) }}" class="w-full rounded-xl border border-slate-300 px-3" @error('name') aria-invalid="true" @enderror></label>
        <label class="grid gap-2 text-sm font-semibold" for="traveler-phone">Phone<input id="traveler-phone" required name="phone" type="tel" inputmode="tel" autocomplete="tel" value="{{ old('phone', auth()->user()->phone) }}" class="w-full rounded-xl border border-slate-300 px-3" @error('phone') aria-invalid="true" @enderror></label>
        <label class="grid gap-2 text-sm font-semibold sm:col-span-2" for="traveler-avatar">Profile photo<input id="traveler-avatar" type="file" name="avatar" accept="image/*" class="w-full rounded-xl border border-slate-300 p-3 text-sm"></label>
        <label class="grid gap-2 text-sm font-semibold sm:col-span-2" for="traveler-bio">Bio<textarea id="traveler-bio" name="bio" rows="4" class="w-full rounded-xl border border-slate-300 p-3">{{ old('bio', $profile->bio) }}</textarea></label>
        <label class="grid gap-2 text-sm font-semibold" for="traveler-currency">Preferred currency<input id="traveler-currency" required name="preferred_currency" type="text" inputmode="text" autocapitalize="characters" value="{{ old('preferred_currency', $profile->preferred_currency) }}" maxlength="3" class="w-full rounded-xl border border-slate-300 px-3" @error('preferred_currency') aria-invalid="true" @enderror></label>
        <div class="sm:col-span-2"><button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-ink px-6 text-sm font-bold text-white shadow-soft sm:w-auto">Save profile</button></div>
    </form>
</div>
@endsection
