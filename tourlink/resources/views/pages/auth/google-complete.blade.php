@extends('layouts.auth')
@section('title', 'Complete your account | TourLink')
@section('content')
<div class="auth-wrapper auth-compact-shell">
    <section class="auth-card auth-compact-card" aria-labelledby="google-complete-heading">
        <header class="auth-compact-header">
            <h1 id="google-complete-heading">Complete your account</h1>
            <p>One more detail before we set up your TourLink account.</p>
        </header>

        <div class="google-profile-summary">
            @if (! empty($profile['picture']))
                <img src="{{ $profile['picture'] }}" alt="" width="48" height="48" referrerpolicy="no-referrer">
            @endif
            <div>
                <strong>{{ $profile['name'] }}</strong>
                <span>{{ $profile['email'] }}</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="auth-msg auth-msg-error" role="alert" aria-live="assertive" tabindex="-1">
                <strong>We couldn't complete your account.</strong>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('google.complete') }}" data-auth-form data-auth-loading-label="Creating account...">
            @csrf
            <div class="auth-field">
                <label for="phone">Phone number</label>
                <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" value="{{ old('phone', $profile['phone'] ?? '') }}" placeholder="0712345678 or +254712345678" required @if ($errors->has('phone')) aria-invalid="true" aria-describedby="google-phone-error" @endif>
                @error('phone')
                    <span id="google-phone-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>

            @if ($needsAccountType)
                <div class="auth-field">
                    <label for="role">Account type</label>
                    <select id="role" name="role" required>
                        <option value="TRAVELER" @selected(old('role', $initialRole) === 'TRAVELER')>Traveler</option>
                        <option value="OPERATOR" @selected(old('role', $initialRole) === 'OPERATOR')>Tour operator</option>
                        <option value="VEHICLE_OWNER" @selected(old('role', $initialRole) === 'VEHICLE_OWNER')>Vehicle owner</option>
                    </select>
                </div>
            @endif

            <div class="auth-actions">
                <button class="auth-btn auth-compact-submit" type="submit" data-auth-submit>
                    <span data-auth-submit-label>Continue</span>
                    <svg data-submit-arrow viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    <span class="auth-login-spinner" data-auth-spinner aria-hidden="true"></span>
                </button>
            </div>
        </form>

        <p class="auth-footer"><a href="{{ route('login') }}">Cancel and return to sign in</a></p>
    </section>
</div>
@endsection
