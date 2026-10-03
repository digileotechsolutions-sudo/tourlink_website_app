<details class="public-support-launcher">
    <summary class="public-support-launcher__button" aria-label="Open human support chat">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/>
        </svg>
        <span>Chat with us</span>
    </summary>
    <section class="public-support-launcher__panel" aria-label="Human customer support">
        <header class="public-support-launcher__header">
            <span class="support-online-dot" aria-hidden="true"></span>
            <div>
                <h2>Havenedge Tourlink Support</h2>
                <p>Real people here to help</p>
            </div>
        </header>
        <p class="public-support-launcher__message">Have a question about a trip or booking? Start a private conversation with our support team.</p>
        @auth
            @if (auth()->user()->role === \App\Role::Traveler)
                <a class="public-support-launcher__action" href="{{ route('support.index') }}">Open support chat</a>
            @else
                <p class="public-support-launcher__note">Customer support chat is available from a traveler account.</p>
            @endif
        @else
            <a class="public-support-launcher__action" href="{{ route('support.index') }}">Sign in to chat</a>
            <a class="public-support-launcher__register" href="{{ route('register') }}">Create an account</a>
        @endauth
    </section>
</details>
