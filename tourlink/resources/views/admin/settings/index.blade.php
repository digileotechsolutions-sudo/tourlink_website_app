@extends('layouts.admin')

@section('title', 'System settings | Havenedge Tourlink Admin')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="border-b border-slate-200 pb-6"><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Administration</p><h1 class="mt-2 text-3xl font-black text-slate-950">System settings</h1><p class="mt-1 text-sm text-slate-600">Configure payment providers and manage application settings. Stored values are never displayed.</p></header>
        @if(session('status'))<p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>@endif
        @if($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950"><p class="font-bold">The setting could not be saved.</p>@foreach($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div>@endif

        @include('admin.settings.payment-gateways')

        <section class="mt-7 border-b border-slate-200 pb-7" aria-labelledby="create-setting-heading">
            <h2 id="create-setting-heading" class="text-lg font-extrabold text-slate-950">Add setting</h2>
            <form method="POST" action="{{ route('admin.settings.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[minmax(180px,0.7fr)_minmax(240px,1fr)_auto]">@csrf
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Key<input name="key" required maxlength="255" pattern="[A-Z][A-Z0-9_.-]*" placeholder="FEATURE_FLAG" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Value<input type="password" name="value" required maxlength="10000" autocomplete="new-password" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                <button class="self-end rounded bg-emerald-800 px-4 py-2.5 text-sm font-bold text-white">Add setting</button>
            </form>
        </section>

        <section class="mt-7" aria-labelledby="settings-list-heading">
            <div class="flex items-baseline justify-between border-b border-slate-200 pb-3"><h2 id="settings-list-heading" class="text-lg font-extrabold text-slate-950">Stored settings</h2><span class="text-sm text-slate-500">{{ number_format($settings->total()) }} keys</span></div>
            @forelse($settings as $setting)
                <article class="grid gap-4 border-b border-slate-200 py-5 lg:grid-cols-[minmax(180px,0.7fr)_minmax(0,1fr)] lg:items-center">
                    <div><h3 class="font-mono text-sm font-bold text-slate-950">{{ $setting->key }}</h3><p class="mt-1 text-xs text-slate-500">Updated {{ $setting->updated_at?->format('M j, Y H:i') }} · Value stored</p></div>
                    <form method="POST" action="{{ route('admin.settings.update', $setting) }}" class="grid gap-3 sm:grid-cols-[minmax(140px,0.8fr)_minmax(180px,1fr)_auto]">@csrf @method('PUT')
                        <label class="grid gap-1 text-xs font-semibold text-slate-700">Key<input name="key" value="{{ $setting->key }}" required maxlength="255" pattern="[A-Z][A-Z0-9_.-]*" class="min-h-10 rounded border border-slate-300 px-3 font-mono text-xs font-normal"></label>
                        <label class="grid gap-1 text-xs font-semibold text-slate-700">Replace value<input type="password" name="value" maxlength="10000" autocomplete="new-password" placeholder="Leave blank to keep current" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                        <button class="self-end rounded border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-800 hover:bg-slate-50">Save</button>
                    </form>
                </article>
            @empty<p class="py-8 text-sm text-slate-600">No settings are stored yet.</p>@endforelse
            <div class="mt-5">{{ $settings->links() }}</div>
        </section>
    </div>
@endsection