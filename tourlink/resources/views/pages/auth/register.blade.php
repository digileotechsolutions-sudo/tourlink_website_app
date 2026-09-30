@extends('layouts.auth')
@section('title', 'Create an account | TourLink')
@section('content')
<div class="auth-wrapper">
    <section class="auth-left"><a class="auth-brand-mark" href="{{ route('home') }}">TL</a><span class="auth-kicker">Travel with confidence</span><h1>Your next journey starts here</h1><p>Join a trusted community for memorable trips, local operators, and easy vehicle hire.</p><div class="auth-feature-list"><span>Curated Kenyan experiences</span><span>Trusted local providers</span></div></section>
    <section class="auth-right"><div class="auth-card">
        <header class="auth-header"><h2>Create account</h2><p class="auth-subtitle">Set up your TourLink account in a minute.</p></header>
        <form method="POST" action="{{ route('register') }}">@csrf
            <div class="auth-field"><label for="name">Your name</label><input id="name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" placeholder="Amina Kariuki" required>@error('name')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="auth-field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com" required>@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="auth-field"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" placeholder="0712345678" required>@error('phone')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="auth-field auth-field-password"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="new-password" required><button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Show password" title="Show password">Show</button>@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="auth-field auth-field-password"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button class="auth-password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show confirmation password" title="Show confirmation password">Show</button></div>
            <div class="auth-field"><label for="role">Account type</label><select id="role" name="role" required><option value="TRAVELER" @selected(old('role', $initialRole) === 'TRAVELER')>Traveler</option><option value="OPERATOR" @selected(old('role', $initialRole) === 'OPERATOR')>Tour operator</option><option value="VEHICLE_OWNER" @selected(old('role', $initialRole) === 'VEHICLE_OWNER')>Vehicle owner</option></select></div>
            <div class="auth-actions"><button class="auth-btn" type="submit">Create account <span aria-hidden="true">→</span></button></div>
        </form>
        <p class="auth-footer">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
    </div></section>
</div>
@endsection
<div>
    <!-- Simplicity is the essence of happiness. - Cedric Bledsoe -->
</div>
