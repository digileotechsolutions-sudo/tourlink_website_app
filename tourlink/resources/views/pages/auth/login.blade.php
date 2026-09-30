@extends('layouts.auth')
@section('title', 'Sign in | TourLink')
@section('content')
<div class="auth-wrapper">
    <section class="auth-left"><a class="auth-brand-mark" href="{{ route('home') }}">TL</a><span class="auth-kicker">Travel with confidence</span><h1>Welcome back</h1><p>Pick up where you left off and keep every trip in one place.</p><div class="auth-feature-list"><span>Curated Kenyan experiences</span><span>Trusted local providers</span></div></section>
    <section class="auth-right"><div class="auth-card">
        <header class="auth-header"><h2>Sign in</h2><p class="auth-subtitle">Access your bookings, favorites, and messages.</p></header>
        @if(session('status'))<p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>@endif
        <form method="POST" action="{{ route('login') }}">@csrf
            <div class="auth-field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="auth-field auth-field-password"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required><button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Show password" title="Show password">Show</button>@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="auth-actions"><label class="auth-form-check"><input name="remember" type="checkbox" value="1"><span>Remember me</span></label><button class="auth-btn" type="submit">Sign in <span aria-hidden="true">→</span></button></div>
        </form>
        <a class="auth-forgot" href="{{ route('password.request') }}">Forgot password?</a>
        <p class="auth-footer">New to TourLink? <a href="{{ route('register') }}">Create an account</a></p>
    </div></section>
</div>
@endsection
<div>
    <!-- Simplicity is the essence of happiness. - Cedric Bledsoe -->
</div>
