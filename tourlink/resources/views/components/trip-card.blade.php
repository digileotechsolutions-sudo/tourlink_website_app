@props(['trip'])
@php($imageUrl = $trip->images->first()?->url ?? $trip->destination?->image_url)
@php($verification = $trip->operator?->verification_level?->value ?? 'BASIC')

<article class="group overflow-hidden rounded-2xl bg-white shadow-soft transition duration-300 hover:-translate-y-1 hover:shadow-card">
    <a href="{{ route('trips.show', ['trip' => $trip->slug]) }}" class="block">
        <div class="relative h-52 overflow-hidden bg-sand">
            @if($imageUrl)<img class="size-full object-cover transition duration-700 group-hover:scale-105" src="{{ $imageUrl }}" alt="{{ $trip->images->first()?->alt ?? $trip->name }}" loading="lazy">@endif
            <div class="absolute inset-0 bg-ink/45"></div>
            <span class="absolute left-4 top-4 rounded-full px-2.5 py-1 text-[10px] font-extrabold tracking-[.08em] {{ $verification === 'TRUSTED' ? 'bg-sun/90 text-ink' : 'bg-white/90 text-ink' }}">{{ $verification }}</span>
            <div class="absolute bottom-4 left-4 text-white"><p class="text-xs font-semibold text-white/75">{{ $trip->category?->name }}</p><h3 class="mt-1 text-xl font-extrabold">{{ $trip->name }}</h3></div>
        </div>
        <div class="p-4">
            <div class="mb-4 flex items-center justify-between gap-3"><span class="truncate text-xs font-semibold text-slate-500">{{ $trip->destination?->name }}</span><span class="shrink-0 text-xs font-bold text-ink">{{ $trip->reviews_avg_rating ? number_format((float) $trip->reviews_avg_rating, 1) : 'New' }} <span class="font-normal text-slate-400">({{ $trip->reviews_count }})</span></span></div>
            <div class="flex items-end justify-between gap-3"><div><span class="text-xs text-slate-400">from</span><p class="text-lg font-black text-ink">KES {{ number_format($trip->price_per_person) }} <span class="text-xs font-medium text-slate-400">/ person</span></p></div><span class="rounded-full bg-sand px-3 py-1.5 text-xs font-bold text-ink">{{ $trip->duration_days }} days</span></div>
        </div>
    </a>
</article>
<div>
    <!-- Simplicity is the essence of happiness. - Cedric Bledsoe -->
</div>
