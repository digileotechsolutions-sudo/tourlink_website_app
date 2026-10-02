@extends('layouts.auth')
@section('title', 'Choose a new password | TourLink')
@section('content')
<div class="auth-wrapper">
    <section class="auth-left">
        <a class="auth-brand-mark" href="{{ route('home') }}" aria-label="TourLink home">TL</a>
        <span class="auth-kicker">Account recovery</span>
        <h1>Choose a new password</h1>
        <p>Use a unique password with at least eight characters.</p>
    </section>
    <section class="auth-right">
        <div class="auth-card">
            <header class="auth-header">
                <h2>New password</h2>
                <p class="auth-subtitle">Your reset link is valid for a limited time.</p>
            </header>
            @if (session('status'))
                <p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <div class="auth-msg auth-msg-error" role="alert">
                    <strong>We couldn't reset your password.</strong>
                    <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('password.update') }}" data-auth-form data-auth-loading-label="Saving password...">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="auth-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" inputmode="email" autocomplete="email" autocapitalize="none" spellcheck="false" value="{{ old('email', $email) }}" required @error('email') aria-invalid="true" aria-describedby="reset-email-error" @enderror>
                    @error('email')<span id="reset-email-error" class="field-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="auth-field auth-field-password">
                    <label for="password">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" data-strength-input required @error('password') aria-invalid="true" aria-describedby="reset-password-error" @enderror>
                    <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Show password" title="Show password">Show</button>
                    @error('password')<span id="reset-password-error" class="field-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <x-password-strength />
                <div class="auth-field auth-field-password">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    <button class="auth-password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show confirmation password" title="Show confirmation password">Show</button>
                </div>
                <div class="auth-actions">
                    <button class="auth-btn auth-compact-submit" type="submit" data-auth-submit>
                        <span data-auth-submit-label>Save new password</span>
                        <span class="auth-login-spinner" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
