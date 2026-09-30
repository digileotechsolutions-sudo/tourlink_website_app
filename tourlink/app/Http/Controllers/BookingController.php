<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripBookingRequest;
use App\Http\Requests\StoreVehicleBookingRequest;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class BookingController extends Controller
{
    public function index(): View
    {
        $bookings = Booking::query()
            ->where('traveler_id', auth()->id())
            ->with(['trip.destination', 'vehicle', 'payments' => fn ($query) => $query->latest('created_at')->limit(1)])
            ->latest('start_date')
            ->paginate(10);

        return view('pages.bookings.index', compact('bookings'));
    }

    public function storeTrip(StoreTripBookingRequest $request, BookingService $bookingService): RedirectResponse
    {
        try {
            $booking = $bookingService->createTripBooking(
                $request->user(),
                $request->string('trip')->toString(),
                $request->integer('travelers'),
                CarbonImmutable::parse($request->date('start_date')),
                $request->input('pickup_location'),
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['booking' => $this->bookingError($exception)]);
        }

        return redirect()->route('bookings.index')->with('status', "Booking {$booking->reference} was created.");
    }

    public function storeVehicle(StoreVehicleBookingRequest $request, BookingService $bookingService): RedirectResponse
    {
        try {
            $booking = $bookingService->createVehicleBooking(
                $request->user(),
                $request->string('vehicle')->toString(),
                CarbonImmutable::parse($request->date('start_date')),
                CarbonImmutable::parse($request->date('end_date')),
                $request->boolean('driver_required'),
                $request->input('pickup_location'),
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['booking' => $this->bookingError($exception)]);
        }

        return redirect()->route('bookings.index')->with('status', "Booking {$booking->reference} was created.");
    }

    private function bookingError(RuntimeException $exception): string
    {
        return match ($exception->getMessage()) {
            'NOT_ENOUGH_SEATS' => 'There are not enough seats available for that departure.',
            'VEHICLE_BOOKED' => 'That vehicle is already booked for those dates.',
            'VEHICLE_UNAVAILABLE' => 'That vehicle is not available for those dates.',
            'TRIP_UNAVAILABLE' => 'That trip is no longer available.',
            default => 'The booking could not be created.',
        };
    }
}
