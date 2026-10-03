<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Role;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderCustomerBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_book_a_trip_as_a_customer(): void
    {
        $operator = User::factory()->create(['role' => Role::Operator]);
        $trip = Trip::factory()->create();
        $booking = new Booking;
        $booking->reference = 'TL-OPERATOR';

        $service = \Mockery::mock(BookingService::class);
        $service->shouldReceive('createTripBooking')
            ->once()
            ->withArgs(fn (...$arguments): bool => $arguments[0]->is($operator))
            ->andReturn($booking);
        $this->app->instance(BookingService::class, $service);

        $this->actingAs($operator)
            ->post(route('bookings.trips.store'), [
                'trip' => $trip->id,
                'travelers' => 1,
                'start_date' => $trip->departure_date->toDateString(),
            ])
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('status', 'Booking TL-OPERATOR was created.');

        $this->actingAs($operator)->get(route('bookings.index'))->assertOk();
    }

    public function test_vehicle_owner_can_hire_a_vehicle_as_a_customer(): void
    {
        $owner = User::factory()->create(['role' => Role::VehicleOwner]);
        $vehicle = Vehicle::factory()->create();
        $booking = new Booking;
        $booking->reference = 'TL-OWNER';

        $service = \Mockery::mock(BookingService::class);
        $service->shouldReceive('createVehicleBooking')
            ->once()
            ->withArgs(fn (...$arguments): bool => $arguments[0]->is($owner))
            ->andReturn($booking);
        $this->app->instance(BookingService::class, $service);

        $this->actingAs($owner)
            ->post(route('bookings.vehicles.store'), [
                'vehicle' => $vehicle->id,
                'start_date' => now()->addDays(20)->toDateString(),
                'end_date' => now()->addDays(22)->toDateString(),
            ])
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('status', 'Booking TL-OWNER was created.');

        $this->actingAs($owner)->get(route('bookings.index'))->assertOk();
    }
}
