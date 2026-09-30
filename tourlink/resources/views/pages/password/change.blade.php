@extends('layouts.auth')
@section('title', 'Change password | TourLink')
@section('content')
<div class="auth-wrapper">
    <section class="auth-left"><a class="auth-brand-mark" href="{{ route('home') }}">TL</a><span class="auth-kicker">Account security</span><h1>Keep your account secure</h1><p>Choose a unique password that is difficult to guess.</p></section>
    <section class="auth-right"><div class="auth-card">
        <header class="auth-header"><h2>Change password</h2><p class="auth-subtitle">Confirm your current password before choosing a new one.</p></header>
        @if(session('status'))<p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>@endif
        <form method="POST" action="{{ route('password.change.update') }}">@csrf @method('PUT')
            <div class="auth-field auth-field-password"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required><button class="auth-password-toggle" type="button" data-password-toggle="current_password" aria-label="Show current password" title="Show current password">Show</button>@error('current_password')<span class="field-error">{{ $message }}</span>@enderror</div>
            <div class="auth-field auth-field-password"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" data-strength-input required><button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Show new password" title="Show new password">Show</button>@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
            <x-password-strength />
            <div class="auth-field auth-field-password"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button class="auth-password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show confirmation password" title="Show confirmation password">Show</button></div>
            <div class="auth-actions"><button class="auth-btn" type="submit">Update password <span aria-hidden="true">→</span></button></div>
        </form>
    </div></section>
</div>
@endsection