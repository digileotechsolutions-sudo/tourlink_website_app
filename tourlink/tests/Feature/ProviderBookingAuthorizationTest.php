<?php

namespace Tests\Feature;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderBookingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_cannot_update_another_operators_trip_booking(): void
    {
        $otherOperator = User::factory()->create(['role' => 'OPERATOR']);
        $trip = Trip::factory()->for($otherOperator, 'operator')->create();
        $booking = Booking::factory()->for($trip)->create(['status' => BookingStatus::Pending]);
        $operator = User::factory()->create(['role' => 'OPERATOR']);

        $this->actingAs($operator)
            ->patch(route('operator.bookings.update', $booking), ['status' => BookingStatus::Confirmed->value])
            ->assertNotFound();

        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
    }

    public function test_vehicle_owner_cannot_update_another_owners_vehicle_booking(): void
    {
        $otherOwner = User::factory()->create(['role' => 'VEHICLE_OWNER']);
        $vehicle = Vehicle::factory()->for($otherOwner, 'owner')->create();
        $booking = Booking::factory()->create([
            'type' => 'VEHICLE',
            'trip_id' => null,
            'vehicle_id' => $vehicle->id,
            'status' => BookingStatus::Pending,
        ]);
        $owner = User::factory()->create(['role' => 'VEHICLE_OWNER']);

        $this->actingAs($owner)
            ->patch(route('vehicle-owner.bookings.update', $booking), ['status' => BookingStatus::Confirmed->value])
            ->assertNotFound();

        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
    }
}
