@extends('layouts.app')
@section('title', 'Talk to us | Havenedge Tourlink')
@section('meta_description', 'Contact Havenedge Tourlink by email or phone for questions, support, complaints, and other concerns.')
@section('content')
@php
    $supportEmail = config('services.tourlink.support_email');
    $supportPhone = config('services.tourlink.support_phone');
    $supportPhoneSecondary = config('services.tourlink.support_phone_secondary');
    $supportLocation = config('services.tourlink.location') ?: 'Nairobi, Kenya';
@endphp
<div class="contact-page">
    <header class="contact-page__hero">
        <div class="container-page">
            <p class="eyebrow text-sun">Havenedge Tourlink support</p>
            <h1 class="display">Talk to us.</h1>
            <p>Questions, feedback, or need help finding the right service? Reach our team directly.</p>
        </div>
    </header>

    <main class="container-page contact-page__content">
        <div class="contact-page__intro">
            <p class="eyebrow">Contact our team</p>
            <h2>We’re here to help.</h2>
            <p>For booking questions, message your provider from the booking in your account. For general questions, account support, or provider enquiries, use one of the contacts below.</p>
        </div>

        <div class="contact-methods" aria-label="Contact methods">
            @if ($supportEmail)
                <a class="contact-method" href="mailto:{{ $supportEmail }}">
                    <span class="contact-method__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span>
                    <span class="contact-method__copy"><span>Email us</span><strong>{{ $supportEmail }}</strong></span>
                    <span class="contact-method__arrow" aria-hidden="true">&rarr;</span>
                </a>
            @endif
            @if ($supportPhone)
                <a class="contact-method" href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}">
                    <span class="contact-method__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6.7 3.5h2.4l1.2 4.1-1.8 1.6a14 14 0 0 0 6.3 6.3l1.6-1.8 4.1 1.2v2.4a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.7 5.7a2 2 0 0 1 2-2.2Z"/></svg></span>
                    <span class="contact-method__copy"><span>Call our team</span><strong>{{ $supportPhone }}</strong></span>
                    <span class="contact-method__arrow" aria-hidden="true">&rarr;</span>
                </a>
            @endif
            @if ($supportPhoneSecondary)
                <a class="contact-method" href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhoneSecondary) }}">
                    <span class="contact-method__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6.7 3.5h2.4l1.2 4.1-1.8 1.6a14 14 0 0 0 6.3 6.3l1.6-1.8 4.1 1.2v2.4a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.7 5.7a2 2 0 0 1 2-2.2Z"/></svg></span>
                    <span class="contact-method__copy"><span>Alternate phone</span><strong>{{ $supportPhoneSecondary }}</strong></span>
                    <span class="contact-method__arrow" aria-hidden="true">&rarr;</span>
                </a>
            @endif
            <a class="contact-method" href="https://havenedgerealtors.com">
                <span class="contact-method__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18m0-18a14 14 0 0 0 0 18"/></svg></span>
                <span class="contact-method__copy"><span>Visit our website</span><strong>havenedgerealtors.com</strong></span>
                <span class="contact-method__arrow" aria-hidden="true">&nearr;</span>
            </a>
            <div class="contact-method contact-method--location">
                <span class="contact-method__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                <span class="contact-method__copy"><span>Our location</span><strong>{{ $supportLocation }}</strong></span>
            </div>
        </div>

        <p class="contact-page__note">For assistance with a confirmed booking, sign in and contact the service provider directly from your booking details.</p>
    </main>
</div>
@endsection