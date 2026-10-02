<footer class="portal-footer" aria-label="TourLink information">
    <span>© {{ now()->year }} TourLink</span>
    <nav aria-label="Footer links">
        <a href="{{ route('home') }}">Visit website</a>
        @if (Route::has('privacy'))<a href="{{ route('privacy') }}">Privacy</a>@endif
        @if (Route::has('terms'))<a href="{{ route('terms') }}">Terms</a>@endif
    </nav>
</footer>
