<?php

namespace Tests\Feature;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelerReviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_booking_owner_can_submit_a_review(): void
    {
        $traveler = User::factory()->create(['role' => 'TRAVELER']);
        $booking = Booking::factory()->for($traveler, 'traveler')->create(['status' => BookingStatus::Completed]);

        $this->actingAs($traveler)
            ->post(route('traveler.reviews.store', $booking), ['rating' => 5, 'body' => 'Great experience'])
            ->assertRedirect(route('traveler.section', ['section' => 'reviews']));

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'author_id' => $traveler->id,
            'trip_id' => $booking->trip_id,
        ]);
    }

    public function test_other_traveler_cannot_review_a_completed_booking(): void
    {
        $traveler = User::factory()->create(['role' => 'TRAVELER']);
        $otherTraveler = User::factory()->create(['role' => 'TRAVELER']);
        $booking = Booking::factory()->for($traveler, 'traveler')->create(['status' => BookingStatus::Completed]);

        $this->actingAs($otherTraveler)
            ->post(route('traveler.reviews.store', $booking), ['rating' => 5, 'body' => 'Not my booking'])
            ->assertNotFound();

        $this->assertDatabaseMissing('reviews', ['booking_id' => $booking->id]);
    }

    public function test_traveler_cannot_review_a_booking_that_is_not_completed(): void
    {
        $traveler = User::factory()->create(['role' => 'TRAVELER']);
        $booking = Booking::factory()->for($traveler, 'traveler')->create(['status' => BookingStatus::Confirmed]);

        $this->actingAs($traveler)
            ->post(route('traveler.reviews.store', $booking), ['rating' => 5, 'body' => 'Too soon'])
            ->assertNotFound();

        $this->assertDatabaseMissing('reviews', ['booking_id' => $booking->id]);
    }
}
