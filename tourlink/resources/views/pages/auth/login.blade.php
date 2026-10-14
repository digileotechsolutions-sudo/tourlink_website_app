@extends('layouts.auth')
@section('title', 'Sign in | Havenedge Tourlink')
@section('content')
<div class="auth-wrapper auth-login-shell">
    <section class="auth-login-card" aria-labelledby="login-heading">
        <header class="auth-login-header">
            <span class="auth-heading-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v5c0 4.6 2.8 8.1 7 10 4.2-1.9 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
            </span>
            <p class="auth-login-eyebrow">{{ __('Havenedge Tourlink account') }}</p>
            <h1 id="login-heading">{{ __('Welcome Back') }}</h1>
            <p>{{ __('Sign in to continue your journey.') }}</p>
        </header>
        @if(session('status'))<p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>@endif
        @error('google')<p class="auth-msg auth-msg-error" role="alert">{{ $message }}</p>@enderror
        @if ($errors->any())
            <div class="auth-msg auth-msg-error" role="alert" aria-live="assertive">
                <p class="font-bold">{{ __('We could not sign you in.') }}</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('login') }}" data-auth-form data-no-loading>
            @csrf
            @if ($continueToSupport)<input type="hidden" name="continue" value="support">@endif
            <div class="auth-login-field">
                <label for="email">{{ __('Email address') }}</label>
                <div class="auth-login-control">
                    <svg class="auth-login-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m5 7 7 5 7-5"/></svg>
                    <input id="email" name="email" type="email" inputmode="email" autocomplete="username" autocapitalize="none" spellcheck="false" value="{{ old('email') }}" placeholder="you@example.com" required autofocus @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror>
                </div>
                @error('email')<span class="field-error" id="login-email-error" role="alert">{{ $message }}</span>@enderror
            </div>
            <div class="auth-login-field">
                <div class="auth-login-label-row"><label for="password">{{ __('Password') }}</label><a href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a></div>
                <div class="auth-login-control">
                    <svg class="auth-login-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7M12 14.5v2.5"/></svg>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="{{ __('Enter your password') }}" required @error('password') aria-invalid="true" aria-describedby="login-password-error" @enderror>
                    <button class="auth-login-toggle" type="button" data-password-toggle="password" data-show-label="{{ __('Show password') }}" data-hide-label="{{ __('Hide password') }}" aria-label="{{ __('Show password') }}" title="{{ __('Show password') }}">
                        <svg data-password-eye viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                        <svg data-password-eye-off hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.1 0 9.5 6 9.5 6a14.8 14.8 0 0 1-3 3.6M6.2 6.9C3.8 8.5 2.5 12 2.5 12s3.4 6 9.5 6c1 0 1.9-.2 2.7-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        <span class="sr-only" data-password-toggle-label>{{ __('Show password') }}</span>
                    </button>
                </div>
                @error('password')<span class="field-error" id="login-password-error" role="alert">{{ $message }}</span>@enderror
            </div>
            <div class="auth-login-actions">
                <label class="auth-login-remember"><input name="remember" type="checkbox" value="1"><span>{{ __('Remember me') }}</span></label>
                <button class="auth-login-submit" type="submit">
                    <span>{{ __('Sign in') }}</span>
                    <svg data-submit-arrow viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </button>
            </div>
        </form>
        @include('components.google-auth-button', ['googleClientId' => $googleClientId, 'googleNonce' => $googleNonce, 'mode' => 'login'])
        <p class="auth-login-footer">{{ __('New to Havenedge Tourlink?') }} <a href="{{ route('register', $continueToSupport ? ['continue' => 'support'] : []) }}">{{ __('Create an account') }}</a></p>
    </section>
</div>
@endsection
