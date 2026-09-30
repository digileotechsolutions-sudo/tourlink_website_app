<footer class="bg-ink py-12 text-white">
    <div class="container-page grid gap-8 sm:grid-cols-[1.2fr_.8fr] sm:items-end">
        <div>
            <a href="{{ route('home') }}" class="text-xl font-black tracking-[-.04em]">TOUR<span class="text-sun">link</span></a>
            <p class="mt-3 max-w-md text-sm leading-6 text-white/60">Discover trips, hire verified vehicles and connect with trusted local providers across Kenya.</p>
        </div>
        <nav class="flex flex-wrap gap-x-6 gap-y-3 text-sm font-semibold text-white/75 sm:justify-end" aria-label="Footer navigation">
            <a class="hover:text-white" href="{{ route('trips.index') }}">Trips</a>
            <a class="hover:text-white" href="{{ route('vehicles.index') }}">Vehicles</a>
            <a class="hover:text-white" href="{{ route('home') }}#destinations">Destinations</a>
            @if(Route::has('privacy'))<a class="hover:text-white" href="{{ route('privacy') }}">Privacy</a>@endif
            @if(Route::has('terms'))<a class="hover:text-white" href="{{ route('terms') }}">Terms</a>@endif
        </nav>
        <p class="border-t border-white/10 pt-5 text-xs text-white/45 sm:col-span-2">© {{ now()->year }} TourLink. Made for the journey.</p>
    </div>
</footer>
<div>
    <!-- Because you are alive, everything is possible. - Thich Nhat Hanh -->
</div>
