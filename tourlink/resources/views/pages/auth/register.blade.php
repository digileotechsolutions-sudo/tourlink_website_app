@extends('layouts.auth')
@section('title', 'Create an account | TourLink')
@section('content')
<div class="auth-wrapper auth-compact-shell auth-register-shell">
    <section class="auth-card auth-compact-card" aria-labelledby="register-heading">
        <header class="auth-compact-header">
            <h1 id="register-heading">Create your account</h1>
            <p>Start planning your next journey.</p>
        </header>

        @if ($referrer)
            <p class="auth-referrer-note"><strong>{{ $referrer->name }}</strong> invited you to join TourLink. Your referral is credited after verification and approval.</p>
        @endif

        @unless ($verificationAvailable)
            <p class="auth-msg auth-msg-error" role="alert">Signup is temporarily unavailable because account verification delivery is not configured. Please contact TourLink support.</p>
        @endunless

        @if ($errors->any())
            <div class="auth-msg auth-msg-error" role="alert" aria-live="assertive" tabindex="-1">
                <strong>We couldn't create your account.</strong>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" data-auth-form data-form-draft="register" data-auth-loading-label="Creating account...">
            @csrf

            <div class="auth-field">
                <label for="name">Your name</label>
                <input id="name" name="name" type="text" autocomplete="name" autocapitalize="words" data-draft-field value="{{ old('name') }}" placeholder="Amina Musumba" required @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                @error('name')
                    <span id="name-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label for="email">Email address</label>
                <div class="auth-modern-control">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m5 7 7 5 7-5"/></svg>
                    <input id="email" name="email" type="email" inputmode="email" autocomplete="email" autocapitalize="none" spellcheck="false" data-draft-field value="{{ old('email') }}" placeholder="you@example.com" required @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
                </div>
                @error('email')
                    <span id="email-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label for="phone">Phone number</label>
                <div class="auth-modern-control">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6.7 3.5h2.4l1.2 4.1-1.8 1.6a14 14 0 0 0 6.3 6.3l1.6-1.8 4.1 1.2v2.4a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.7 5.7a2 2 0 0 1 2-2.2Z"/></svg>
                    <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" data-draft-field value="{{ old('phone') }}" placeholder="0712345678 or +254712345678" required @if ($errors->has('phone')) aria-invalid="true" aria-describedby="phone-error" @endif>
                </div>
                @error('phone')
                    <span id="phone-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field auth-field-password">
                <label for="password">Password</label>
                <div class="auth-modern-control auth-modern-control-password">
                    <svg class="auth-modern-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7M12 14.5v2.5"/></svg>
                    <input id="password" name="password" type="password" autocomplete="new-password" data-strength-input required @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                    <button class="auth-password-toggle auth-modern-toggle" type="button" data-password-toggle="password" aria-label="Show password" title="Show password">
                        <svg data-password-eye viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                        <svg data-password-eye-off hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.1 0 9.5 6 9.5 6a14.8 14.8 0 0 1-3 3.6M6.2 6.9C3.8 8.5 2.5 12 2.5 12s3.4 6 9.5 6c1 0 1.9-.2 2.7-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        <span class="sr-only" data-password-toggle-label>Show password</span>
                    </button>
                </div>
                @error('password')
                    <span id="password-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <x-password-strength />

            <div class="auth-field auth-field-password">
                <label for="password_confirmation">Confirm password</label>
                <div class="auth-modern-control auth-modern-control-password">
                    <svg class="auth-modern-leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7M12 14.5v2.5"/></svg>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    <button class="auth-password-toggle auth-modern-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show confirmation password" title="Show confirmation password">
                        <svg data-password-eye viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                        <svg data-password-eye-off hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.1 0 9.5 6 9.5 6a14.8 14.8 0 0 1-3 3.6M6.2 6.9C3.8 8.5 2.5 12 2.5 12s3.4 6 9.5 6c1 0 1.9-.2 2.7-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        <span class="sr-only" data-password-toggle-label>Show confirmation password</span>
                    </button>
                </div>
            </div>

            <div class="auth-field">
                <label for="role">Account type</label>
                <select id="role" name="role" data-draft-field required @if ($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
                    <option value="TRAVELER" @selected(old('role', $initialRole) === 'TRAVELER')>Traveler</option>
                    <option value="OPERATOR" @selected(old('role', $initialRole) === 'OPERATOR')>Tour operator</option>
                    <option value="VEHICLE_OWNER" @selected(old('role', $initialRole) === 'VEHICLE_OWNER')>Vehicle owner</option>
                </select>
                @error('role')
                    <span id="role-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-actions">
                <button class="auth-btn auth-compact-submit" type="submit" data-auth-submit>
                    <span data-auth-submit-label>Create account</span>
                    <svg data-submit-arrow viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    <span class="auth-login-spinner" data-auth-spinner aria-hidden="true"></span>
                </button>
            </div>
        </form>

        @include('components.google-auth-button', ['googleClientId' => $googleClientId, 'googleNonce' => $googleNonce, 'mode' => 'register'])

        <p class="auth-footer">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
    </section>
</div>
@endsection