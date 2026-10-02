@props(['vehicle'])
@php($imageUrl = $vehicle->images->first()?->url)
@php($verification = $vehicle->owner?->verification_level?->value ?? 'BASIC')

<article class="group overflow-hidden rounded-2xl bg-white shadow-soft transition duration-300 hover:-translate-y-1 hover:shadow-card">
    <a href="{{ route('vehicles.show', ['vehicle' => $vehicle->slug]) }}" class="block">
        <div class="relative h-48 overflow-hidden bg-sand">
            @if($imageUrl)<img class="size-full object-cover transition duration-700 group-hover:scale-105" src="{{ $imageUrl }}" alt="{{ $vehicle->images->first()?->alt ?? $vehicle->name }}" loading="lazy" decoding="async">@endif
            <span class="absolute left-4 top-4 rounded-full px-2.5 py-1 text-[10px] font-extrabold tracking-[.08em] {{ $verification === 'TRUSTED' ? 'bg-sun/90 text-ink' : 'bg-white/90 text-ink' }}">{{ $verification }}</span>
        </div>
        <div class="p-4">
            <div class="flex items-start justify-between gap-3"><div><h3 class="font-extrabold text-ink">{{ $vehicle->name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $vehicle->body_type }} · {{ $vehicle->location }}</p></div><span class="shrink-0 text-xs font-bold text-ink">{{ $vehicle->reviews_avg_rating ? number_format((float) $vehicle->reviews_avg_rating, 1) : 'New' }} <span class="font-normal text-slate-400">({{ $vehicle->reviews_count }})</span></span></div>
            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-slate-500"><span class="rounded-full bg-mist px-2.5 py-1">{{ $vehicle->seating_capacity }} seats</span>@if($vehicle->four_by_four)<span class="rounded-full bg-mist px-2.5 py-1">4×4</span>@endif @if($vehicle->driver_included)<span class="rounded-full bg-mist px-2.5 py-1">Driver included</span>@endif</div>
            <p class="mt-4 text-lg font-black text-ink">KES {{ number_format($vehicle->price_per_day) }} <span class="text-xs font-medium text-slate-400">/ day</span></p>
        </div>
    </a>
</article>
<div>
    <!-- Very little is needed to make a happy life. - Marcus Aurelius -->
</div>
