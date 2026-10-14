@extends('layouts.app')
@section('title', 'About Havenedge Tourlink')
@section('meta_description', 'Learn how Havenedge Tourlink connects people with trips, vehicles, transport and roadside services.')
@section('content')
@php
    $aboutServices = [
        ['number' => '01', 'title' => __('Tours & travel'), 'description' => __('Discover trips and connect with tour operators and local travel providers.')],
        ['number' => '02', 'title' => __('Vehicle hire'), 'description' => __('Find vehicles for journeys, transfers and the ways you want to explore.')],
        ['number' => '03', 'title' => __('Transport & drivers'), 'description' => __('Explore transport options and connect with available driving services.')],
        ['number' => '04', 'title' => __('Towing & recovery'), 'description' => __('Find roadside assistance and vehicle recovery providers when you need support.')],
        ['number' => '05', 'title' => __('Provider listings'), 'description' => __('Give operators and service providers a place to showcase what they offer.')],
        ['number' => '06', 'title' => __('Booking & communication'), 'description' => __('Make service discovery, requests and conversations easier to manage.')],
    ];
    $aboutPrinciples = [
        ['title' => __('More connected'), 'description' => __('Bring travel, vehicles and mobility services together in one place.')],
        ['title' => __('Clearer choices'), 'description' => __('Help people find services that fit their plans and reach out to providers.')],
        ['title' => __('Built around trust'), 'description' => __('Verification features are designed to support confidence across the platform.')],
        ['title' => __('Opportunity to grow'), 'description' => __('Help local businesses and individuals present their services to more people.')],
    ];
@endphp

<div class="about-redesign">
    <section class="about-redesign-hero">
        <div class="container-page about-redesign-hero__grid">
            <div class="about-redesign-hero__copy">
                <a class="about-redesign-back" href="{{ route('home') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
                    {{ __('Back to home') }}
                </a>
                <p class="about-redesign-kicker"><span></span>{{ __('About Havenedge Tourlink') }}</p>
                <h1>{{ __('Good journeys begin with a connection.') }}</h1>
                <p class="about-redesign-hero__intro">{{ __('We bring people and travel services closer together, making it easier to discover a trip, find a vehicle or connect with the support a journey needs.') }}</p>
                <div class="about-redesign-hero__actions">
                    <a class="about-redesign-button about-redesign-button--yellow" href="{{ route('trips.index') }}">{{ __('Explore trips') }} <span aria-hidden="true">→</span></a>
                    <a class="about-redesign-text-link" href="{{ route('vehicles.index') }}">{{ __('Find a vehicle') }} <span aria-hidden="true">↗</span></a>
                </div>
                <div class="about-redesign-hero__note">
                    <span class="about-redesign-hero__note-mark" aria-hidden="true">HT</span>
                    <span><strong>{{ __('One platform, many ways to go.') }}</strong><small>{{ __('Trips · Vehicles · Transport · Support') }}</small></span>
                </div>
            </div>

            <figure class="about-redesign-photo">
                <img src="{{ asset('images/about-kenya-community.jpg') }}" alt="A Maasai host and a traveler share a cultural activity in a Kenyan village" fetchpriority="high" decoding="async">
                <div class="about-redesign-photo__wash" aria-hidden="true"></div>
                <figcaption>
                    <span class="about-redesign-photo__caption">{{ __('Travel connects us to places — and to one another.') }}</span>
                    <small><a href="https://commons.wikimedia.org/wiki/File:Maasai_people_and_a_tourist_lighting_a_fire_in_a_Maasai_village_on_the_A109_road,_Kenya.jpg" target="_blank" rel="noopener noreferrer">Maasai people and a traveler sharing a cultural activity</a> by CT Cooper · <a href="https://creativecommons.org/publicdomain/zero/1.0/" target="_blank" rel="noopener noreferrer">CC0</a></small>
                </figcaption>
                <span class="about-redesign-photo__stamp" aria-hidden="true">Go<br>together</span>
            </figure>
        </div>
    </section>

    <main>
        <section class="about-redesign-mission">
            <div class="container-page about-redesign-mission__inner">
                <p class="about-redesign-kicker"><span></span>{{ __('Why we are here') }}</p>
                <div class="about-redesign-mission__grid">
                    <h2>{{ __('Make travel and mobility easier to access.') }}</h2>
                    <div>
                        <p>{{ __('Planning a tour, arranging transport or looking for roadside help should feel less fragmented. Havenedge Tourlink is designed to help customers discover services, connect with providers and take the next step with more confidence.') }}</p>
                        <p>{{ __('We bring travellers, vehicle owners, tour operators, drivers and recovery providers into a shared digital space — connecting the people behind the journey.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-redesign-services" aria-labelledby="about-services-title">
            <div class="container-page">
                <header class="about-redesign-section-heading">
                    <div>
                        <p class="about-redesign-kicker"><span></span>{{ __('What you can find here') }}</p>
                        <h2 id="about-services-title">{{ __('Many services. A more connected journey.') }}</h2>
                    </div>
                    <p>{{ __('Explore the services that help people plan, move and get support along the way.') }}</p>
                </header>
                <div class="about-redesign-service-grid">
                    @foreach($aboutServices as $service)
                        <article class="about-redesign-service-card">
                            <span class="about-redesign-service-card__number">{{ $service['number'] }}</span>
                            <span class="about-redesign-service-card__arrow" aria-hidden="true">↗</span>
                            <h3>{{ $service['title'] }}</h3>
                            <p>{{ $service['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="about-redesign-vision">
            <div class="container-page about-redesign-vision__inner">
                <div class="about-redesign-vision__title">
                    <p class="about-redesign-kicker"><span></span>{{ __('Our vision') }}</p>
                    <h2>{{ __('A more connected tourism and mobility ecosystem.') }}</h2>
                </div>
                <div class="about-redesign-principles">
                    @foreach($aboutPrinciples as $principle)
                        <article>
                            <span aria-hidden="true">✳</span>
                            <div><h3>{{ $principle['title'] }}</h3><p>{{ $principle['description'] }}</p></div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="about-redesign-commitment">
            <div class="container-page about-redesign-commitment__inner">
                <div>
                    <p class="about-redesign-kicker"><span></span>{{ __('Our commitment') }}</p>
                    <h2>{{ __('Keep making the journey better.') }}</h2>
                </div>
                <p>{{ __('We are committed to improving the experience for customers and service providers, and to using technology to make tourism, transport and roadside assistance more convenient and connected.') }}</p>
            </div>
        </section>

        <section class="container-page about-redesign-contact" aria-labelledby="about-contact-heading">
            <div class="about-redesign-contact__copy">
                <p class="about-redesign-kicker"><span></span>{{ __('Let’s connect') }}</p>
                <h2 id="about-contact-heading">{{ __('Have a question or need a hand?') }}</h2>
                <p>{{ __('We are here to help with questions, feedback, support and other concerns.') }}</p>
            </div>
            <a class="about-redesign-button about-redesign-button--yellow" href="{{ route('contact') }}">{{ __('Get in touch') }} <span aria-hidden="true">→</span></a>
        </section>
    </main>
</div>
@endsection
