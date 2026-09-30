@extends('layouts.auth')
@section('title', 'Verify your account | TourLink')
@section('content')
<div class="auth-wrapper auth-verification-wrapper">
    <section class="auth-left">
        <a class="auth-brand-mark" href="{{ route('home') }}" aria-label="TourLink home">TL</a>
        <span class="auth-kicker">One more step</span>
        <h1>Verify Your Account</h1>
        <p>Confirm your contact details to keep your TourLink account secure and unlock trusted journeys.</p>
        <div class="auth-feature-list"><span>Private verification</span><span>Six-digit secure codes</span></div>
    </section>
    <section class="auth-right">
        <div class="auth-card auth-verification-card">
            <header class="auth-header">
                <div class="auth-verification-icon" aria-hidden="true">✓</div>
                <h2>Check your contacts</h2>
                <p class="auth-subtitle">We sent a six-digit verification code. Each code expires after five minutes.</p>
            </header>

            @if(session('status'))<p class="auth-msg auth-msg-notice" role="status">{{ session('status') }}</p>@endif
            @if(session('referred_by'))<p class="auth-msg auth-msg-notice" role="status"><strong>{{ session('referred_by') }}</strong> referred you. Your referral is credited once your account is approved.</p>@endif
            @error('code')<p class="auth-msg auth-msg-error" role="alert">{{ $message }}</p>@enderror
            @error('user_id')<p class="auth-msg auth-msg-error" role="alert">{{ $message }}</p>@enderror

            @foreach($verificationMethods as $verification)
                @php
                    $channel = $verification['channel'];
                    $isEmail = $channel === \App\OtpChannel::Email;
                    $recipient = (string) $verification['recipient'];
                    $localPart = $isEmail ? strstr($recipient, '@', true) : '';
                    $domain = $isEmail ? strstr($recipient, '@') : '';
                    $maskedRecipient = $isEmail
                        ? substr($localPart, 0, 2).'***'.$domain
                        : str_repeat('*', max(0, strlen($recipient) - 4)).substr($recipient, -4);
                @endphp
                <div class="auth-verify-group {{ $verification['verified'] ? 'is-complete' : '' }}">
                    <div class="auth-verify-heading">
                        <div>
                            <strong>{{ $verification['label'] }}</strong>
                            <span>{{ $maskedRecipient }}</span>
                        </div>
                        <span class="auth-verify-status {{ $verification['verified'] ? 'is-verified' : '' }}">{{ $verification['verified'] ? 'Verified' : 'Pending' }}</span>
                    </div>

                    @unless($verification['verified'])
                        @if($verification['deliverable'])
                            <form class="auth-verify-controls" method="POST" action="{{ route('verification.verify') }}" data-otp-form>
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <input type="hidden" name="channel" value="{{ $channel->value }}">
                                <input type="hidden" name="code" data-otp-value>
                                <div class="auth-otp-inputs" role="group" aria-label="{{ $verification['label'] }} verification code">
                                    @for($digit = 0; $digit < 6; $digit++)
                                        <input class="auth-otp-digit" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" autocomplete="one-time-code" data-otp-digit data-otp-index="{{ $digit }}" aria-label="Code digit {{ $digit + 1 }}" required>
                                    @endfor
                                </div>
                                <button class="auth-btn auth-verify-button" type="submit"><span data-verify-label>Verify Account</span><span class="auth-loading-spinner" data-verify-spinner aria-hidden="true"></span></button>
                            </form>
                            <form class="auth-resend-form" method="POST" action="{{ route('verification.resend') }}" data-resend-form data-resend-seconds="60">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <input type="hidden" name="channel" value="{{ $channel->value }}">
                                <button class="auth-resend" type="submit" data-resend-button>Resend {{ $isEmail ? 'email' : 'SMS' }} code <span data-resend-countdown></span></button>
                            </form>
                        @else
                            <p class="auth-msg auth-msg-notice">Code delivery for this {{ $isEmail ? 'email address' : 'phone number' }} is not available yet. Contact TourLink support.</p>
                        @endif
                    @endunless
                </div>
            @endforeach

            @if($verificationMethods === [])
                <p class="auth-msg auth-msg-error" role="alert">Account verification delivery is not configured. Please contact TourLink support.</p>
            @endif

            <p class="auth-footer"><a href="{{ route('register') }}">Return to registration</a><span aria-hidden="true"> · </span><a href="{{ route('login') }}">Go to sign in</a></p>
        </div>
    </section>
</div>
@endsection
