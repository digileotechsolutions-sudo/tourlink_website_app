<dialog class="sign-in-prompt" data-sign-in-prompt aria-labelledby="sign-in-prompt-title" aria-describedby="sign-in-prompt-description">
    <div class="sign-in-prompt__card">
        <button class="sign-in-prompt__close" type="button" data-sign-in-prompt-close aria-label="Close sign-in prompt">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <path d="m6 6 12 12M18 6 6 18"/>
            </svg>
        </button>
        <div class="sign-in-prompt__mark" aria-hidden="true">HT</div>
        <p class="sign-in-prompt__eyebrow">Havenedge Tourlink</p>
        <h2 id="sign-in-prompt-title">Keep your journeys together</h2>
        <p id="sign-in-prompt-description">Sign in to manage your bookings, follow payment updates and keep your trip details in one place.</p>
        <a class="sign-in-prompt__primary" href="{{ route('login') }}">Sign in</a>
        <p class="sign-in-prompt__signup">New to Tourlink? <a href="{{ route('register') }}">Create an account</a></p>
    </div>
</dialog>
