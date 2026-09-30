@extends('layouts.auth')
@section('title', 'Sign in | TourLink')
@section('content')
<div class="auth-wrapper auth-login-shell">
    <section class="auth-login-card" aria-labelledby="login-heading">
        <a class="auth-login-logo" href="{{ route('home') }}" aria-label="TourLink home">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="m15.5 8.5-2.2 4.8-4.8 2.2 2.2-4.8 4.8-2.2Z"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/></svg>
        </a>
        <header class="auth-login-header">
            <p class="auth-login-eyebrow">TourLink account</p>
            <h1 id="login-heading">Welcome Back</h1>
            <p>Sign in to continue your journey.</p>
        </header>
        @if(session('status'))<p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>@endif
        <form method="POST" action="{{ route('login') }}" data-auth-form data-auth-loading-label="Signing in...">@csrf
            <div class="auth-login-field">
                <label for="email">Email address</label>
                <div class="auth-login-control">
                    <svg class="auth-login-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m5 7 7 5 7-5"/></svg>
                    <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror>
                </div>
                @error('email')<span class="field-error" id="login-email-error" role="alert">{{ $message }}</span>@enderror
            </div>
            <div class="auth-login-field">
                <div class="auth-login-label-row"><label for="password">Password</label><a href="{{ route('password.request') }}">Forgot password?</a></div>
                <div class="auth-login-control">
                    <svg class="auth-login-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7M12 14.5v2.5"/></svg>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required @error('password') aria-invalid="true" aria-describedby="login-password-error" @enderror>
                    <button class="auth-login-toggle" type="button" data-password-toggle="password" aria-label="Show password" title="Show password">
                        <svg data-password-eye viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                        <svg data-password-eye-off hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.1 0 9.5 6 9.5 6a14.8 14.8 0 0 1-3 3.6M6.2 6.9C3.8 8.5 2.5 12 2.5 12s3.4 6 9.5 6c1 0 1.9-.2 2.7-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        <span class="sr-only" data-password-toggle-label>Show password</span>
                    </button>
                </div>
                @error('password')<span class="field-error" id="login-password-error" role="alert">{{ $message }}</span>@enderror
            </div>
            <div class="auth-login-actions">
                <label class="auth-login-remember"><input name="remember" type="checkbox" value="1"><span>Remember me</span></label>
                <button class="auth-login-submit" type="submit" data-auth-submit>
                    <span data-auth-submit-label>Sign in</span>
                    <svg data-submit-arrow viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    <span class="auth-login-spinner" data-auth-spinner aria-hidden="true"></span>
                </button>
            </div>
        </form>
        <p class="auth-login-footer">New to TourLink? <a href="{{ route('register') }}">Create an account</a></p>
    </section>
</div>
@endsection
<div>
    <!-- Simplicity is the essence of happiness. - Cedric Bledsoe -->
</div>
