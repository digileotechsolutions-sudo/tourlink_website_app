@extends('layouts.app')
@section('title', 'About Havenedge Tourlink')
@section('meta_description', 'Learn about Havenedge Tourlink and how we connect people with tours, vehicles, transport, and roadside services.')
@section('content')
<div class="about-page">
    <header class="about-page__hero">
        <div class="container-page about-page__hero-inner">
            <div class="about-page__hero-copy">
                <p class="eyebrow">About us</p>
                <h1 class="display">Havenedge Tourlink</h1>
                <p class="about-page__tagline">Connecting People, Vehicles, Tours and Travel Services</p>
                <p>Havenedge Tourlink is a digital platform designed to make travel, tourism, transportation, and roadside assistance easier, more accessible, and more connected.</p>
                <p>We bring together <strong>tourists, travellers, vehicle owners, tour operators, drivers, and towing/recovery operators</strong> on one convenient platform, making it easier for users to discover services, connect with service providers, and arrange the support they need.</p>
            </div>
            <div class="about-page__hero-media">
                <img src="{{ asset('images/about-kenya-community.jpg') }}" alt="A Maasai host and a traveler work together to light a fire in a Kenyan village" fetchpriority="high" decoding="async">
                <span>
                    Kenyan people connecting through travel
                    <small><a href="https://commons.wikimedia.org/wiki/File:Maasai_people_and_a_tourist_lighting_a_fire_in_a_Maasai_village_on_the_A109_road,_Kenya.jpg" target="_blank" rel="noopener noreferrer">Maasai people and a traveler sharing a cultural activity</a> by CT Cooper · <a href="https://creativecommons.org/publicdomain/zero/1.0/" target="_blank" rel="noopener noreferrer">CC0</a></small>
                </span>
            </div>
        </div>
    </header>

    <main class="container-page about-page__content">
        <section class="about-page__section" aria-labelledby="about-mission-heading">
            <p class="eyebrow">Our mission</p>
            <h2 id="about-mission-heading">Make travel and mobility easier to access.</h2>
            <p>Our mission is to simplify the way people access travel and mobility services by creating a trusted digital environment where customers and service providers can connect efficiently.</p>
            <p>Whether you are planning a tour, looking for a vehicle, arranging transportation, or need towing and roadside assistance, Havenedge Tourlink is designed to help you find and connect with the right service provider.</p>
        </section>

        <section class="about-page__section" aria-labelledby="about-services-heading">
            <p class="eyebrow">What we do</p>
            <h2 id="about-services-heading">Services and connections in one place.</h2>
            <dl class="about-services">
                <div><dt>Tour &amp; Travel Services</dt><dd>Discover and connect with tour operators and travel service providers.</dd></div>
                <div><dt>Vehicle Services</dt><dd>Connect vehicle owners and customers for available transportation solutions.</dd></div>
                <div><dt>Towing &amp; Recovery</dt><dd>Find towing and vehicle recovery service providers when assistance is needed.</dd></div>
                <div><dt>Driver &amp; Transport Services</dt><dd>Connect customers with available transport and driving services.</dd></div>
                <div><dt>Service Provider Listings</dt><dd>Allow businesses and individuals to showcase their services and reach potential customers.</dd></div>
                <div><dt>Digital Booking &amp; Communication</dt><dd>Make it easier for customers and service providers to communicate and manage service requests.</dd></div>
            </dl>
        </section>

        <section class="about-page__section" aria-labelledby="about-vision-heading">
            <p class="eyebrow">Our vision</p>
            <h2 id="about-vision-heading">A connected tourism and mobility ecosystem.</h2>
            <p>We envision a connected tourism and mobility ecosystem where finding a tour, vehicle, driver, or roadside assistance service is simple, transparent, and convenient.</p>
            <p>Through technology, we aim to connect more people with reliable service providers while creating opportunities for businesses and individuals to grow their reach.</p>
        </section>

        <section class="about-page__section" aria-labelledby="about-why-heading">
            <p class="eyebrow">Why Havenedge Tourlink?</p>
            <h2 id="about-why-heading">Designed to make the next step clearer.</h2>
            <dl class="about-reasons">
                <div><dt>One Platform</dt><dd>Access different tourism, transport, vehicle, and roadside services through one digital platform.</dd></div>
                <div><dt>Easy Connections</dt><dd>Connect customers with service providers based on their needs and available services.</dd></div>
                <div><dt>Verified Information</dt><dd>We provide verification features designed to help improve trust and confidence within the platform.</dd></div>
                <div><dt>Convenient Access</dt><dd>Designed as a modern web and PWA platform that can be accessed conveniently from compatible devices.</dd></div>
                <div><dt>Opportunities for Service Providers</dt><dd>Vehicle owners, tour operators, drivers, and towing operators can showcase their services and connect with potential customers.</dd></div>
            </dl>
        </section>

        <section class="about-page__section" aria-labelledby="about-commitment-heading">
            <p class="eyebrow">Our commitment</p>
            <h2 id="about-commitment-heading">Keep improving the journey.</h2>
            <p>At Havenedge Tourlink, we are committed to continuously improving the platform and creating a better experience for both customers and service providers.</p>
            <p>We believe technology can make tourism, transportation, and roadside assistance more connected and convenient.</p>
            <p class="about-page__closing"><strong>Havenedge Tourlink — Connecting People, Vehicles, Tours and Travel Services.</strong></p>
        </section>

        <section id="about-contact" class="about-page__contact" aria-labelledby="about-contact-heading">
            <div>
                <p class="eyebrow">Contact us</p>
                <h2 id="about-contact-heading">Talk to Havenedge Tourlink.</h2>
                <p>For questions, complaints, support, or other concerns, contact us using the details below.</p>
            </div>
            <a class="about-page__contact-link" href="{{ route('contact') }}">Visit our contact page <span aria-hidden="true">&rarr;</span></a>
        </section>
    </main>
</div>
@endsection