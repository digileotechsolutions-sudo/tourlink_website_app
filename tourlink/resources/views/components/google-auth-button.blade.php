@if ($googleClientId && $googleNonce)
    <div class="google-auth-block" data-google-auth data-google-client-id="{{ $googleClientId }}" data-google-nonce="{{ $googleNonce }}">
        <div class="google-auth-divider" aria-hidden="true"><span>OR</span></div>
        <div class="google-auth-button-wrap">
            <div data-google-button></div>
        </div>
        <p class="google-auth-status" data-google-auth-status role="status" aria-live="polite" hidden></p>
        <p class="field-error google-auth-error" data-google-auth-error role="alert" aria-live="assertive" hidden></p>
        <form method="POST" action="{{ route('google.authenticate') }}" data-google-credential-form hidden>
            @csrf
            <input type="hidden" name="mode" value="{{ $mode }}">
            @if ($mode === 'register')
                <input type="hidden" name="role" value="{{ $initialRole ?? 'TRAVELER' }}">
            @endif
            <input type="hidden" name="credential" value="">
        </form>
    </div>
@endif
