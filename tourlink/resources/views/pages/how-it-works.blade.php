@extends('layouts.app')
@section('title', 'How It Works | Havenedge Tourlink')
@section('meta_description', 'See how Havenedge Tourlink connects travellers and customers with tours, vehicles, drivers, and roadside service providers.')
@section('content')
<div class="how-page">
    <header class="how-hero">
        <div class="container-page how-hero__grid">
            <div class="how-hero__copy">
                <p class="how-eyebrow">How Havenedge Tourlink works</p>
                <h1>Your Journey Starts With a Simple Connection</h1>
                <p>Havenedge Tourlink makes it easy to discover services, connect with service providers, request assistance and arrange your journey from one convenient platform.</p>
                <div class="how-hero__actions">
                    <a class="how-button how-button--primary" href="{{ route('trips.index') }}">Explore Services <span aria-hidden="true">&rarr;</span></a>
                    <a class="how-button how-button--light" href="{{ route('register') }}">Get Started</a>
                </div>
            </div>
            <figure class="how-hero__image">
                <img src="https://images.unsplash.com/photo-1535338454770-8be927b5a00b?auto=format&fit=crop&w=1000&q=80" srcset="https://images.unsplash.com/photo-1535338454770-8be927b5a00b?auto=format&fit=crop&w=640&q=75 640w, https://images.unsplash.com/photo-1535338454770-8be927b5a00b?auto=format&fit=crop&w=1000&q=80 1000w, https://images.unsplash.com/photo-1535338454770-8be927b5a00b?auto=format&fit=crop&w=1300&q=82 1300w" sizes="(max-width: 767px) calc(100vw - 32px), 48vw" alt="Open road through a Kenyan travel landscape" fetchpriority="high" decoding="async">
                <figcaption><span class="how-live-dot" aria-hidden="true"></span>Travel, mobility and local services</figcaption>
            </figure>
        </div>
    </header>

    <nav class="how-jump-nav" aria-label="How it works sections">
        <div class="container-page">
            <a href="#customers">Customers</a>
            <a href="#tour-operators">Tour operators</a>
            <a href="#vehicle-owners">Vehicle owners</a>
            <a href="#towing">Towing &amp; recovery</a>
            <a href="#trust">Trust</a>
        </div>
    </nav>

    <main>
        <section id="customers" class="how-section container-page">
            <div class="how-section__heading">
                <p class="how-eyebrow">For customers &amp; travellers</p>
                <h2>From searching to setting off.</h2>
                <p>Find a service, understand the details, and connect with the provider before your journey begins.</p>
            </div>
            <ol class="how-step-grid how-step-grid--five">
                <li class="how-step"><span class="how-step__number">01</span><h3>Create your account</h3><p>Sign up with your basic information and verify your email. Add a phone number for contact; phone OTP verification is not currently required.</p></li>
                <li class="how-step"><span class="how-step__number">02</span><h3>Find a service</h3><p>Explore tours, vehicles, drivers, towing, and travel services. Use search and filters to narrow down available listings.</p></li>
                <li class="how-step"><span class="how-step__number">03</span><h3>View service details</h3><p>Review the description, provider, location, availability, price, and reviews where available.</p></li>
                <li class="how-step"><span class="how-step__number">04</span><h3>Request or book</h3><p>Choose a service and send a request with the date, location, and other details the provider needs.</p></li>
                <li class="how-step"><span class="how-step__number">05</span><h3>Connect &amp; travel</h3><p>Communicate with the provider, confirm the arrangements, and proceed with your journey or requested service.</p></li>
            </ol>
        </section>

        <section id="tour-operators" class="how-section how-section--tint">
            <div class="container-page">
                <div class="how-section__heading">
                    <p class="how-eyebrow">For tour operators</p>
                    <h2>Bring your tours to more travellers.</h2>
                    <p>Build your business profile, publish experiences, and manage requests from your provider dashboard.</p>
                </div>
                <ol class="how-step-grid how-step-grid--five">
                    <li class="how-step"><span class="how-step__number">01</span><h3>Create your provider profile</h3><p>Register and add your company name, contact information, and business details.</p></li>
                    <li class="how-step"><span class="how-step__number">02</span><h3>Complete verification</h3><p>Submit the required information and business documents where applicable.</p></li>
                    <li class="how-step"><span class="how-step__number">03</span><h3>Add your tours</h3><p>Share tour names, destinations, descriptions, images, pricing, duration, availability, meeting points, and inclusions.</p></li>
                    <li class="how-step"><span class="how-step__number">04</span><h3>Receive customer requests</h3><p>Customers can discover your published tours and send booking requests.</p></li>
                    <li class="how-step"><span class="how-step__number">05</span><h3>Manage your bookings</h3><p>Review upcoming bookings, communicate with customers, and manage tour availability in your dashboard.</p></li>
                </ol>
                <a class="how-inline-link" href="{{ route('register', ['role' => 'OPERATOR']) }}">Join as a tour operator <span aria-hidden="true">&rarr;</span></a>
            </div>
        </section>

        <section id="vehicle-owners" class="how-section container-page">
            <div class="how-section__heading">
                <p class="how-eyebrow">For vehicle owners</p>
                <h2>Make your vehicle available.</h2>
                <p>Create a listing with the details customers need to make an informed request.</p>
            </div>
            <ol class="how-step-grid how-step-grid--five">
                <li class="how-step"><span class="how-step__number">01</span><h3>Register</h3><p>Create your Havenedge Tourlink account and add your business details.</p></li>
                <li class="how-step"><span class="how-step__number">02</span><h3>Verify your details</h3><p>Provide the required identity, ownership, and vehicle information where applicable.</p></li>
                <li class="how-step"><span class="how-step__number">03</span><h3>List your vehicle</h3><p>Add photos, make and model, capacity, availability, pricing, features, and location.</p></li>
                <li class="how-step"><span class="how-step__number">04</span><h3>Receive requests</h3><p>Customers can discover your listing and send service or booking requests.</p></li>
                <li class="how-step"><span class="how-step__number">05</span><h3>Manage your services</h3><p>Manage availability, bookings, and customer communication from your dashboard.</p></li>
            </ol>
            <a class="how-inline-link" href="{{ route('register', ['role' => 'VEHICLE_OWNER']) }}">Join as a vehicle owner <span aria-hidden="true">&rarr;</span></a>
        </section>

        <section id="towing" class="how-section how-section--emergency">
            <div class="container-page">
                <div class="how-section__heading">
                    <p class="how-eyebrow">For towing &amp; recovery operators</p>
                    <h2>Roadside help, step by step.</h2>
                    <p>Where towing and recovery services are available, customers can share the details operators need to review a request.</p>
                </div>
                <ol class="how-step-grid how-step-grid--five">
                    <li class="how-step"><span class="how-step__number">01</span><h3>Go online</h3><p>Set your availability to online when you are ready to receive requests.</p></li>
                    <li class="how-step"><span class="how-step__number">02</span><h3>Receive a request</h3><p>A request may include the current location, vehicle type, issue, destination, and additional information.</p></li>
                    <li class="how-step"><span class="how-step__number">03</span><h3>Review the request</h3><p>Check the details and decide whether you can provide the requested assistance.</p></li>
                    <li class="how-step"><span class="how-step__number">04</span><h3>Accept &amp; connect</h3><p>Accept the request and communicate with the customer to confirm arrangements.</p></li>
                    <li class="how-step"><span class="how-step__number">05</span><h3>Provide assistance</h3><p>Travel to the agreed location and provide the requested towing or recovery service.</p></li>
                </ol>
                <p class="how-note">Service availability depends on participating providers and the area. <a href="{{ route('contact') }}">Talk to us about provider services.</a></p>
            </div>
        </section>

        <section class="how-section container-page">
            <div class="how-section__heading">
                <p class="how-eyebrow">The platform at a glance</p>
                <h2>One clear connection from need to service.</h2>
            </div>
            <ol class="how-connection" aria-label="Customer service connection sequence">
                <li><span class="how-connection__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span><strong>Customer / tourist</strong></li>
                <li><span class="how-connection__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg></span><strong>Search</strong></li>
                <li><span class="how-connection__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 11 1.5-5h11L19 11m-15 0h16v8H4zM7 19v2m10-2v2"/></svg></span><strong>Service provider</strong></li>
                <li><span class="how-connection__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18"/></svg></span><strong>Request / book</strong></li>
                <li><span class="how-connection__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></span><strong>Confirm</strong></li>
                <li><span class="how-connection__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 12h18M13 4l8 8-8 8"/></svg></span><strong>Service</strong></li>
            </ol>
        </section>

        <section class="how-section how-section--tint">
            <div class="container-page">
                <div class="how-section__heading">
                    <p class="how-eyebrow">What you can do</p>
                    <h2>Built around practical next steps.</h2>
                </div>
                <div class="how-feature-grid">
                    <article class="how-feature"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span><h3>Discover</h3><p>Find tourism, transportation, and roadside services.</p></article>
                    <article class="how-feature"><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg></span><h3>Search</h3><p>Look for services based on your needs.</p></article>
                    <article class="how-feature"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h10M4 18h6m7-3 3 3 4-6"/></svg></span><h3>Compare</h3><p>Review service information before deciding.</p></article>
                    <article class="how-feature"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M21 11.5a8.5 8.5 0 0 1-12.3 7.6L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5Z"/></svg></span><h3>Connect</h3><p>Communicate with service providers.</p></article>
                    <article class="how-feature"><span aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18"/></svg></span><h3>Book</h3><p>Submit service requests or bookings.</p></article>
                    <article class="how-feature"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19V5m0 14h17M8 15l3-4 3 2 5-7"/></svg></span><h3>Manage</h3><p>Manage bookings, services, and messages.</p></article>
                </div>
            </div>
        </section>

        <section id="trust" class="how-section container-page">
            <div class="how-trust">
                <div>
                    <p class="how-eyebrow">Trust &amp; verification</p>
                    <h2>Designed With Trust in Mind</h2>
                    <p>Havenedge Tourlink includes account and service-provider verification features designed to help users make more informed connections. Verification does not guarantee a provider’s quality, safety, or reliability.</p>
                </div>
                <ul>
                    <li><span aria-hidden="true">✓</span>Email verification</li>
                    <li><span aria-hidden="true">✓</span>Phone number on file for contact (phone OTP verification is not currently required)</li>
                    <li><span aria-hidden="true">✓</span>Provider information</li>
                    <li><span aria-hidden="true">✓</span>Vehicle information where applicable</li>
                    <li><span aria-hidden="true">✓</span>Business information where applicable</li>
                </ul>
            </div>
        </section>

        <section class="how-section how-section--tint">
            <div class="container-page">
                <div class="how-section__heading">
                    <p class="how-eyebrow">After you request a service</p>
                    <h2>What happens after a booking?</h2>
                </div>
                <ol class="how-timeline">
                    <li><span>01</span><h3>Request</h3><p>Customer submits a request.</p></li>
                    <li><span>02</span><h3>Review</h3><p>Provider reviews the request.</p></li>
                    <li><span>03</span><h3>Accept</h3><p>Provider accepts or responds.</p></li>
                    <li><span>04</span><h3>Confirm</h3><p>Both sides confirm arrangements.</p></li>
                    <li><span>05</span><h3>Service</h3><p>The agreed service is provided.</p></li>
                    <li><span>06</span><h3>Complete</h3><p>Customer may leave feedback where available.</p></li>
                </ol>
            </div>
        </section>

        <section class="how-final-cta">
            <div class="container-page">
                <p class="how-eyebrow">Start exploring</p>
                <h2>Ready to Get Started?</h2>
                <p>Whether you're planning a trip, looking for a vehicle, offering tours or need roadside assistance, Havenedge Tourlink helps connect you with the services you need.</p>
                <div class="how-hero__actions">
                    <a class="how-button how-button--primary" href="{{ route('trips.index') }}">Explore Services <span aria-hidden="true">&rarr;</span></a>
                    <a class="how-button how-button--light" href="{{ route('register') }}">Create an Account</a>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection