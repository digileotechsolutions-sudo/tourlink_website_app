@extends('layouts.traveler')

@section('title', 'Support | Havenedge Tourlink')

@section('content')
    <div class="support-unavailable-page">
        <header class="support-unavailable-heading">
            <span class="support-unavailable-mark" aria-hidden="true">HT</span>
            <p>HAVENEDGE TOURLINK SUPPORT</p>
            <h1>We’re still here to help.</h1>
            <p class="support-unavailable-intro">Live chat is temporarily unavailable. Please try again shortly.</p>
        </header>

        <section class="support-unavailable-card" aria-labelledby="support-contact-heading">
            <h2 id="support-contact-heading">Your account is safe</h2>
            <p class="support-unavailable-note">We couldn’t load chat just now. Your account and bookings are safe. Return to any page and retry the chat button shortly.</p>
        </section>

        <a class="support-unavailable-back" href="{{ route($dashboardRoute) }}">Return to your dashboard</a>
    </div>
@endsection
