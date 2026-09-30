<?php

namespace App\Http\Controllers;

use App\BookingStatus;
use App\BookingType;
use App\Models\AdminLog;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AdminBookingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'type' => ['nullable', Rule::enum(BookingType::class)],
        ]);

        $bookings = Booking::query()
            ->with(['traveler:id,name,email', 'trip:id,name,slug', 'vehicle:id,name,slug', 'payments:id,booking_id,status,amount'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('traveler', fn (Builder $traveler): Builder => $traveler->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type): Builder => $query->where('type', $type))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.bookings.index', compact('bookings', 'filters'));
    }

    public function show(Booking $booking): View
    {
        $booking->load(['traveler', 'trip.destination', 'vehicle.destination', 'payments.refunds', 'passengers']);

        return view('admin.bookings.show', compact('booking'));
    }

    public function cancel(Request $request, Booking $booking, BookingService $bookingService): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);

        try {
            $cancelled = $bookingService->cancelByAdmin($request->user(), $booking->id, $data['reason']);
        } catch (RuntimeException $exception) {
            $message = match ($exception->getMessage()) {
                'PAYMENT_REQUIRES_REFUND' => 'This booking has a settled payment. Review it through the refund workflow instead of cancelling it here.',
                'BOOKING_STATE_REQUIRES_FINANCE_REVIEW' => 'This booking is not in a cancellable state.',
                default => 'The booking could not be cancelled.',
            };

            throw ValidationException::withMessages(['booking' => $message]);
        }

        DB::transaction(function () use ($request, $cancelled, $data): void {
            AdminLog::query()->create([
                'admin_id' => $request->user()->id,
                'action' => 'booking.cancelled_by_admin',
                'entity' => 'Booking',
                'entity_id' => $cancelled->id,
                'metadata' => ['reference' => $cancelled->reference, 'reason' => $data['reason']],
            ]);
        });

        return redirect()->route('admin.bookings.show', $cancelled)->with('status', 'Booking cancelled.');
    }
}
