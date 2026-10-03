@props(['mode' => 'trips', 'search' => ''])

<div class="rounded-2xl border border-slate-100 bg-white p-2 shadow-card ring-1 ring-slate-200/80">
    <div class="mb-2 flex w-fit gap-1 rounded-xl bg-sand p-1" role="tablist" aria-label="{{ __('Search for') }}">
        <a class="rounded-lg px-5 py-3 text-xs font-extrabold {{ $mode === 'trips' ? 'bg-ink text-white shadow-sm' : 'text-slate-500 hover:text-ink' }}" href="{{ route('trips.index', ['search' => $search ?: null]) }}" aria-current="{{ $mode === 'trips' ? 'page' : 'false' }}">{{ __('Trips') }}</a>
        <a class="rounded-lg px-5 py-3 text-xs font-extrabold {{ $mode === 'vehicles' ? 'bg-ink text-white shadow-sm' : 'text-slate-500 hover:text-ink' }}" href="{{ route('vehicles.index', ['search' => $search ?: null]) }}" aria-current="{{ $mode === 'vehicles' ? 'page' : 'false' }}">{{ __('Vehicles') }}</a>
    </div>
    <form class="grid gap-2 sm:grid-cols-[1fr_auto]" method="GET" action="{{ $mode === 'vehicles' ? route('vehicles.index') : route('trips.index') }}">
        <label class="flex min-h-14 items-center gap-3 rounded-xl px-3 hover:bg-mist focus-within:ring-2 focus-within:ring-lagoon/20">
            <span class="text-sun" aria-hidden="true"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg></span>
            <span class="min-w-0 flex-1 py-2"><span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Where to?') }}</span><input class="w-full bg-transparent text-sm font-bold text-ink outline-none placeholder:font-normal placeholder:text-slate-400" name="search" value="{{ $search }}" placeholder="{{ __('Destination or experience') }}" type="search"></span>
        </label>
        <button class="flex min-h-14 items-center justify-center gap-2 rounded-xl bg-ink px-6 text-sm font-extrabold text-white hover:bg-lagoon" type="submit"><svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>{{ __('Search') }}</button>
    </form>
</div>
<div>
    <!-- Well begun is half done. - Aristotle -->
</div>
