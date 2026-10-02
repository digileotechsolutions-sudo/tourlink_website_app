@php
    $socialLinks = collect([
        'Facebook' => config('services.tourlink.social.facebook'),
        'Instagram' => config('services.tourlink.social.instagram'),
        'X' => config('services.tourlink.social.x'),
        'LinkedIn' => config('services.tourlink.social.linkedin'),
    ])->filter();
    $supportEmail = config('services.tourlink.support_email');
    $supportPhone = config('services.tourlink.support_phone');
    $supportPhoneSecondary = config('services.tourlink.support_phone_secondary');
    $supportLocation = config('services.tourlink.location');
@endphp

<footer class="site-footer bg-ink text-white">
    <div class="container-page grid gap-10 py-12 sm:py-14 md:grid-cols-2 xl:grid-cols-7">
        <section class="md:col-span-2" aria-labelledby="footer-brand-heading">
            <a id="footer-brand-heading" href="{{ route('home') }}" class="inline-flex items-center gap-3" aria-label="Havenedge Tourlink home">
                <span class="grid size-11 place-items-center rounded-xl bg-sun text-sm font-black text-ink" aria-hidden="true">HT</span>
                <span class="text-2xl font-black tracking-[-.05em]">TOUR<span class="text-sun">link</span></span>
            </a>
            <p class="mt-4 max-w-sm text-sm leading-6 text-white/70">Discover memorable trips, hire verified vehicles, and connect with trusted local travel providers across Kenya.</p>
            @if ($supportEmail || $supportPhone || $supportPhoneSecondary || $supportLocation)
                <address class="mt-5 grid gap-2 text-sm not-italic text-white/75">
                    @if ($supportEmail)<a class="footer-link w-fit" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>@endif
                    @if ($supportPhone)<a class="footer-link w-fit" href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}">{{ $supportPhone }}</a>@endif
                    @if ($supportPhoneSecondary)<a class="footer-link w-fit" href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhoneSecondary) }}">{{ $supportPhoneSecondary }}</a>@endif
                    @if ($supportLocation)<span>{{ $supportLocation }}</span>@endif
                </address>
            @endif
        </section>

        <nav class="grid content-start gap-3" aria-labelledby="footer-quick-links-heading">
            <h2 id="footer-quick-links-heading" class="text-sm font-extrabold text-white">Quick links</h2>
            <a class="footer-link" href="{{ route('home') }}">Home</a>
            <a class="footer-link" href="{{ route('about') }}">About us</a>
            <a class="footer-link" href="{{ route('home') }}#destinations">Destinations</a>
            <a class="footer-link" href="{{ route('contact') }}">Talk to us</a>
            <a class="footer-link" href="{{ route('blog.index') }}">Travel journal</a>
        </nav>

        <nav class="grid content-start gap-3" aria-labelledby="footer-services-heading">
            <h2 id="footer-services-heading" class="text-sm font-extrabold text-white">Services</h2>
            <a class="footer-link" href="{{ route('trips.index') }}">Find a trip</a>
            <a class="footer-link" href="{{ route('vehicles.index') }}">Hire a vehicle</a>
            <a class="footer-link" href="{{ route('compare') }}">Compare trips</a>
            @if (Route::has('register'))
                <a class="footer-link" href="{{ route('register', ['role' => 'OPERATOR']) }}">Become a provider</a>
            @endif
        </nav>

        <section class="grid content-start gap-3" aria-labelledby="footer-support-heading">
            <h2 id="footer-support-heading" class="text-sm font-extrabold text-white">Customer support</h2>
            <a class="footer-link" href="{{ route('home') }}#how-it-works">Help center</a>
            <a class="footer-link" href="#footer-faqs">FAQs</a>
            <a class="footer-link" href="{{ route('contact') }}">Contact us</a>
        </section>

        <nav class="grid content-start gap-3" aria-labelledby="footer-legal-heading">
            <h2 id="footer-legal-heading" class="text-sm font-extrabold text-white">Legal</h2>
            @if (Route::has('privacy'))<a class="footer-link" href="{{ route('privacy') }}">Privacy policy</a>@else<span class="text-sm text-white/50" aria-disabled="true">Privacy policy</span>@endif
            @if (Route::has('terms'))<a class="footer-link" href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms and conditions <span class="sr-only">(opens in a new tab)</span></a>@else<span class="text-sm text-white/50" aria-disabled="true">Terms and conditions</span>@endif
        </nav>

        @if ($socialLinks->isNotEmpty())
            <nav class="grid content-start gap-3" aria-labelledby="footer-social-heading">
                <h2 id="footer-social-heading" class="text-sm font-extrabold text-white">Follow us</h2>
                @foreach ($socialLinks as $network => $url)
                    <a class="footer-link" href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $network }} <span class="sr-only">(opens in a new tab)</span></a>
                @endforeach
            </nav>
        @endif

        <details id="footer-faqs" class="footer-details md:col-span-2 xl:col-span-7">
            <summary>Frequently asked questions</summary>
            <div class="grid gap-4 border-t border-white/10 pt-4 sm:grid-cols-2">
                <div><h3 class="text-sm font-bold text-white">How do I book a trip or vehicle?</h3><p class="mt-1 text-sm leading-6 text-white/65">Browse a listing, choose your dates and details, then send a booking request. You can follow its status from your account.</p></div>
                <div><h3 class="text-sm font-bold text-white">How do I list a trip or vehicle?</h3><p class="mt-1 text-sm leading-6 text-white/65">Create a provider account, complete your profile, and submit the required listing and verification information for review.</p></div>
            </div>
        </details>

        <p class="border-t border-white/15 pt-5 text-xs text-white/55 md:col-span-2 xl:col-span-7">© {{ now()->year }} Havenedge Tourlink. All rights reserved. Made for the journey.</p>
    </div>
</footer>
