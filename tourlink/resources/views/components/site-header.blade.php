<header class="site-header sticky top-0 z-40 border-b border-white/10 bg-ink text-white">
    <div class="container-page flex h-[76px] items-center justify-between gap-6">
        <a class="flex items-center gap-2.5" href="{{ route('home') }}" aria-label="TourLink home">
            <span class="grid size-9 place-items-center rounded-xl bg-sun text-ink" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2.2 4.8-4.8 2.2 2.2-4.8 4.8-2.2Z"/></svg>
            </span>
            <span><span class="block text-[21px] font-black tracking-[-.06em]">TOUR<span class="text-sun">link</span></span><span class="hidden text-[8px] font-bold uppercase tracking-[.18em] text-white/50 sm:block">Trips · Vehicles · Together</span></span>
        </a>
        <nav class="hidden items-center gap-7 text-sm font-semibold text-white/75 lg:flex" aria-label="Main navigation">
            <a class="hover:text-white" href="{{ route('trips.index') }}">Find a trip</a>
            <a class="hover:text-white" href="{{ route('vehicles.index') }}">Hire a vehicle</a>
            <a class="hover:text-white" href="{{ route('blog.index') }}">Journal</a>
            <a class="hover:text-white" href="{{ route('home') }}#destinations">Destinations</a>
        </nav>
        <div class="hidden items-center gap-4 sm:flex">
            @auth
                @if(auth()->user()->role === \App\Role::Admin && Route::has('admin.dashboard'))<a class="text-sm font-bold text-white/80 hover:text-white" href="{{ route('admin.dashboard') }}">Admin review</a><a class="text-sm font-bold text-white/80 hover:text-white" href="{{ route('admin.users.index') }}">Users</a><a class="text-sm font-bold text-white/80 hover:text-white" href="{{ route('admin.verification.index') }}">Verification</a><a class="text-sm font-bold text-white/80 hover:text-white" href="{{ route('admin.trips.index') }}">Manage trips</a><a class="text-sm font-bold text-white/80 hover:text-white" href="{{ route('admin.vehicles.index') }}">Manage vehicles</a><a class="text-sm font-bold text-white/80 hover:text-white" href="{{ route('admin.catalog.index') }}">Catalog</a><a class="text-sm font-bold text-white/80 hover:text-white" href="{{ route('admin.audit.index') }}">Audit log</a>@elseif(Route::has('dashboard'))<a class="text-sm font-bold text-white/80 hover:text-white" href="{{ route('dashboard') }}">Dashboard</a>@endif
                @if(Route::has('logout'))<form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-full bg-sun px-5 py-3 text-sm font-extrabold text-ink hover:bg-orange-300" type="submit">Log out</button></form>@endif
            @else
                @if(Route::has('login'))<a class="text-sm font-bold text-white/80 hover:text-white" href="{{ route('login') }}">Log in</a>@endif
                @if(Route::has('register'))<a class="rounded-full bg-sun px-5 py-3 text-sm font-extrabold text-ink hover:bg-orange-300" href="{{ route('register') }}">Join TourLink</a>@endif
            @endauth
        </div>
        <details class="relative lg:hidden">
            <summary class="grid size-11 cursor-pointer list-none place-items-center rounded-lg p-2 hover:bg-white/10" aria-label="Open navigation menu">
                <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </summary>
            <div class="absolute right-0 top-14 z-50 grid min-w-56 gap-2 rounded-xl border border-white/10 bg-ink p-4 shadow-xl">
                <a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('trips.index') }}">Find a trip</a>
                <a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('vehicles.index') }}">Hire a vehicle</a>
                <a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('blog.index') }}">Journal</a>
                <a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('home') }}#destinations">Destinations</a>
                @auth
                    @if(auth()->user()->role === \App\Role::Admin && Route::has('admin.dashboard'))<a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('admin.dashboard') }}">Admin review</a><a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('admin.users.index') }}">Users</a><a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('admin.verification.index') }}">Verification</a><a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('admin.trips.index') }}">Manage trips</a><a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('admin.vehicles.index') }}">Manage vehicles</a><a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('admin.catalog.index') }}">Catalog</a><a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('admin.audit.index') }}">Audit log</a>@endif
                @endauth
                @guest
                    @if(Route::has('login'))<a class="rounded-lg px-3 py-3 font-semibold text-white/85 hover:bg-white/10" href="{{ route('login') }}">Log in</a>@endif
                    @if(Route::has('register'))<a class="rounded-full bg-sun px-4 py-3 text-center text-sm font-extrabold text-ink" href="{{ route('register') }}">Join TourLink</a>@endif
                @endguest
            </div>
        </details>
    </div>
</header>
<div>
    <!-- We must ship. - Taylor Otwell -->
</div>
