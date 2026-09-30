@extends('layouts.auth')
@section('title', 'Reset your password | TourLink')
@section('content')
<div class="auth-wrapper auth-compact-shell auth-forgot-shell">
    <section class="auth-card auth-compact-card" aria-labelledby="forgot-heading">
        <a class="auth-login-logo" href="{{ route('home') }}" aria-label="TourLink home"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="m15.5 8.5-2.2 4.8-4.8 2.2 2.2-4.8 4.8-2.2Z"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/></svg></a>
        <header class="auth-compact-header"><p class="auth-login-eyebrow">Account recovery</p><h1 id="forgot-heading">Reset your password</h1><p>We’ll email you a secure link if the address is registered.</p></header>
        @if(session('status'))<p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>@endif
        <form method="POST" action="{{ route('password.email') }}" data-auth-form data-auth-loading-label="Sending link...">@csrf
            <div class="auth-field"><label for="email">Email address</label><div class="auth-modern-control"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m5 7 7 5 7-5"/></svg><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com" required></div>@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="auth-actions"><button class="auth-btn auth-compact-submit" type="submit" data-auth-submit><span data-auth-submit-label>Send reset link</span><svg data-submit-arrow viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg><span class="auth-login-spinner" data-auth-spinner aria-hidden="true"></span></button></div>
        </form>
        <p class="auth-footer"><a href="{{ route('login') }}">Back to Login</a></p>
    </section>
</div>
@endsection
<div>
    <!-- Nothing worth having comes easy. - Theodore Roosevelt -->
</div>
