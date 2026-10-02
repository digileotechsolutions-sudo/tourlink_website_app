@php($version = config('app.version'))
<footer class="portal-footer" aria-label="Havenedge Tourlink information">
    <span>© {{ now()->year }} Havenedge Tourlink</span>
    @if ($version)<span class="portal-footer__version">Version {{ $version }}</span>@endif
    <nav aria-label="Application information">
        <a href="{{ route('home') }}#how-it-works">Help</a>
        <a href="{{ route('home') }}#footer-contact">Support</a>
        @if (Route::has('privacy'))<a href="{{ route('privacy') }}">Privacy policy</a>@endif
        @if (Route::has('terms'))<a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms</a>@endif
    </nav>
</footer>
