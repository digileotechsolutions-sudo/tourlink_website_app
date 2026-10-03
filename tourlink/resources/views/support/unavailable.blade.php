@extends('layouts.traveler')

@section('title', 'Support | Havenedge Tourlink')

@section('content')
    <div class="support-unavailable-page">
        <header class="support-unavailable-heading">
            <span class="support-unavailable-mark" aria-hidden="true">HT</span>
            <p>HAVENEDGE TOURLINK SUPPORT</p>
            <h1>We’re still here to help.</h1>
            <p class="support-unavailable-intro">Live chat is temporarily unavailable. Please contact our team directly and we’ll be happy to assist.</p>
        </header>

        <section class="support-unavailable-card" aria-labelledby="support-contact-heading">
            <h2 id="support-contact-heading">Contact our team</h2>
            <p>Choose the contact method that works best for you.</p>
            <div class="support-unavailable-actions">
                @if ($supportEmail)
                    <a class="support-unavailable-link" href="mailto:{{ $supportEmail }}">
                        <span class="support-unavailable-link-icon" aria-hidden="true">✉</span>
                        <span><small>Email support</small><strong>{{ $supportEmail }}</strong></span>
                    </a>
                @endif
                @if ($supportPhone)
                    <a class="support-unavailable-link" href="tel:{{ preg_replace('/[^\d+]/', '', $supportPhone) }}">
                        <span class="support-unavailable-link-icon" aria-hidden="true">☎</span>
                        <span><small>Call support</small><strong>{{ $supportPhone }}</strong></span>
                    </a>
                @endif
            </div>
            <p class="support-unavailable-note">Your account and bookings are safe. Please try live chat again later.</p>
        </section>

        <a class="support-unavailable-back" href="{{ route($dashboardRoute) }}">Return to your dashboard</a>
    </div>
@endsection
