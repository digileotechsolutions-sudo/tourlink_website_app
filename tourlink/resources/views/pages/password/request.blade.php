@extends('layouts.auth')
@section('title', 'Reset your password | TourLink')
@section('content')
<div class="auth-wrapper"><section class="auth-left"><a class="auth-brand-mark" href="{{ route('home') }}">TL</a><span class="auth-kicker">Account recovery</span><h1>Find your way back</h1><p>We’ll send a secure password reset link if the email belongs to a TourLink account.</p></section><section class="auth-right"><div class="auth-card"><header class="auth-header"><h2>Reset password</h2><p class="auth-subtitle">Enter the email address on your account.</p></header>@if(session('status'))<p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>@endif<form method="POST" action="{{ route('password.email') }}">@csrf<div class="auth-field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required>@error('email')<span class="field-error">{{ $message }}</span>@enderror</div><div class="auth-actions"><button class="auth-btn" type="submit">Send reset link <span aria-hidden="true">→</span></button></div></form><p class="auth-footer"><a href="{{ route('login') }}">Back to sign in</a></p></div></section></div>
@endsection
<div>
    <!-- Nothing worth having comes easy. - Theodore Roosevelt -->
</div>
