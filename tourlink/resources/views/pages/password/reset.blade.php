@extends('layouts.auth')
@section('title', __('Choose a new password').' | Havenedge Tourlink')
@section('content')
<div class="auth-wrapper auth-form-wrapper">
    <section class="auth-left">
        <a class="auth-brand-mark" href="{{ route('home') }}" aria-label="Havenedge Tourlink home">HT</a>
        <span class="auth-kicker">{{ __('Account recovery') }}</span>
        <h1>{{ __('Choose a new password') }}</h1>
        <p>{{ __('Use a unique password with at least eight characters.') }}</p>
    </section>
    <section class="auth-right">
        <div class="auth-card">
            <header class="auth-header">
                <h2>{{ __('New password') }}</h2>
                <p class="auth-subtitle">{{ __('Your reset link is valid for a limited time.') }}</p>
            </header>
            @if (session('status'))
                <p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <div class="auth-msg auth-msg-error" role="alert">
                    <strong>{{ __('We could not reset your password.') }}</strong>
                    <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('password.update') }}" data-auth-form data-auth-loading-label="{{ __('Saving password...') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="auth-field">
                    <label for="email">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" inputmode="email" autocomplete="email" autocapitalize="none" spellcheck="false" value="{{ old('email', $email) }}" required @error('email') aria-invalid="true" aria-describedby="reset-email-error" @enderror>
                    @error('email')<span id="reset-email-error" class="field-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="auth-field auth-field-password">
                    <label for="password">{{ __('New password') }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" data-strength-input required @error('password') aria-invalid="true" aria-describedby="reset-password-error" @enderror>
                    <button class="auth-password-toggle" type="button" data-password-toggle="password" data-show-label="{{ __('Show password') }}" data-hide-label="{{ __('Hide password') }}" aria-label="{{ __('Show password') }}" title="{{ __('Show password') }}"><span data-password-toggle-label>{{ __('Show') }}</span></button>
                    @error('password')<span id="reset-password-error" class="field-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <x-password-strength />
                <div class="auth-field auth-field-password">
                    <label for="password_confirmation">{{ __('Confirm password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    <button class="auth-password-toggle" type="button" data-password-toggle="password_confirmation" data-show-label="{{ __('Show password') }}" data-hide-label="{{ __('Hide password') }}" aria-label="{{ __('Show password') }}" title="{{ __('Show password') }}"><span data-password-toggle-label>{{ __('Show') }}</span></button>
                </div>
                <div class="auth-actions">
                    <button class="auth-btn auth-compact-submit" type="submit" data-auth-submit>
                        <span data-auth-submit-label>{{ __('Save new password') }}</span>
                        <span class="auth-login-spinner" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
