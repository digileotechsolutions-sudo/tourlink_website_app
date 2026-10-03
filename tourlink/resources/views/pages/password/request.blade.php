@extends('layouts.auth')
@section('title', __('Reset your password').' | Havenedge Tourlink')
@section('content')
<div class="auth-wrapper auth-compact-shell auth-forgot-shell">
    <section class="auth-card auth-compact-card" aria-labelledby="forgot-heading">
        <header class="auth-compact-header"><p class="auth-login-eyebrow">{{ __('Account recovery') }}</p><h1 id="forgot-heading">{{ __('Reset your password') }}</h1><p>{{ __('We’ll email you a secure link if the address is registered.') }}</p></header>
        @if(session('status'))<p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>@endif
        <form method="POST" action="{{ route('password.email') }}" data-auth-form data-auth-loading-label="{{ __('Sending link...') }}">@csrf
            <div class="auth-field"><label for="email">{{ __('Email address') }}</label><div class="auth-modern-control"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m5 7 7 5 7-5"/></svg><input id="email" name="email" type="email" inputmode="email" autocomplete="email" autocapitalize="none" spellcheck="false" value="{{ old('email') }}" placeholder="you@example.com" required @error('email') aria-invalid="true" aria-describedby="forgot-email-error" @enderror></div>@error('email')<span id="forgot-email-error" class="field-error" role="alert">{{ $message }}</span>@enderror</div>
            <div class="auth-actions"><button class="auth-btn auth-compact-submit" type="submit" data-auth-submit><span data-auth-submit-label>{{ __('Send reset link') }}</span><svg data-submit-arrow viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg><span class="auth-login-spinner" data-auth-spinner aria-hidden="true"></span></button></div>
        </form>
        <p class="auth-footer"><a href="{{ route('login') }}">{{ __('Back to Login') }}</a></p>
    </section>
</div>
@endsection
<div>
    <!-- Nothing worth having comes easy. - Theodore Roosevelt -->
</div>
