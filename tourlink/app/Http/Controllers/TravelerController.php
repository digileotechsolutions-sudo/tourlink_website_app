<?php

namespace App\Http\Controllers;

use App\BookingStatus;
use App\ListingStatus;
use App\Models\Booking;
use App\Models\Favorite;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Trip;
use App\Services\Booking\BookingService;
use App\Services\Payments\PaymentService;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class TravelerController extends Controller
{
    public function dashboard(Request $request): View
    {
        $bookings = $this->travelerBookings($request);

        return view('traveler.dashboard', [
            'bookingCount' => (clone $bookings)->count(),
            'upcomingCount' => (clone $bookings)->where('start_date', '>=', now())->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::Refunded])->count(),
            'favoriteCount' => Favorite::query()->where('user_id', $request->user()->id)->count(),
            'pendingPaymentCount' => (clone $bookings)->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])->count(),
            'recentBookings' => (clone $bookings)->with(['trip:id,name', 'vehicle:id,name'])->latest()->limit(6)->get(),
        ]);
    }

    public function profile(Request $request): View
    {
        $profile = $request->user()->travelerProfile()->firstOrCreate([], ['preferred_currency' => 'KES']);

        return view('traveler.profile', compact('profile'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'phone' => ['required', 'string', 'max:30'], 'bio' => ['nullable', 'string', 'max:5000'], 'preferred_currency' => ['required', 'string', 'size:3'], 'avatar' => ['nullable', 'image', 'max:5120']]);
        $request->user()->update(['name' => $data['name'], 'phone' => $data['phone']]);
        unset($data['name'], $data['phone'], $data['avatar']);
        if ($request->hasFile('avatar')) {
            $request->user()->forceFill(['avatar_url' => Storage::disk('public')->url($request->file('avatar')->store('traveler-avatars', 'public'))])->save();
        }
        $request->user()->travelerProfile()->updateOrCreate([], $data);

        return back()->with('status', 'Profile updated.');
    }

    public function bookings(Request $request, PaymentService $payments): View
    {
        $bookings = $this->travelerBookings($request)
            ->with(['trip.destination', 'vehicle', 'payments.refunds'])
            ->latest('start_date')
            ->paginate(12);
        $bookings->getCollection()->each(function ($booking) use ($payments): void {
            $booking->setAttribute('payment_summary', $payments->summary($booking));
            $booking->setAttribute('payment_in_flight', $payments->hasPaymentInFlight($booking));
        });

        return view('traveler.bookings', compact('bookings'));
    }

    public function cancelBooking(Request $request, Booking $booking, BookingService $bookingService): RedirectResponse
    {
        abort_unless($booking->traveler_id === $request->user()->id, 404);
        try {
            $bookingService->cancel($request->user(), $booking->id, $request->input('reason'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['booking' => match ($exception->getMessage()) {
                'PAYMENT_REQUIRES_REFUND' => 'Paid bookings require a refund review before cancellation.',
                'PAYMENT_PROCESSING' => 'A payment is processing or awaiting bank verification. Resolve it before cancelling the booking.',
                'NOT_CANCELLABLE' => 'This booking can no longer be cancelled.',
                default => 'The booking could not be cancelled.',
            }]);
        }

        return back()->with('status', 'Booking cancelled according to the booking policy.');
    }

    public function favorites(Request $request): View
    {
        return view('traveler.favorites', ['favorites' => Favorite::query()->where('user_id', $request->user()->id)->with('trip.destination')->latest()->paginate(12)]);
    }

    public function saveFavorite(Request $request, Trip $trip): JsonResponse|RedirectResponse
    {
        abort_unless($trip->status === ListingStatus::Published && $trip->verification_status === VerificationStatus::Approved, 404);

        $data = $request->validate([
            'trip_id' => ['required', 'string', 'max:36'],
            'expected_user_id' => ['required', 'string', 'max:36'],
        ]);

        abort_unless(hash_equals($trip->id, $data['trip_id']), 404);
        abort_unless(hash_equals($request->user()->id, $data['expected_user_id']), 403);

        Favorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'trip_id' => $trip->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'saved' => true]);
        }

        return back()->with('status', 'Trip saved to favorites.');
    }

    public function toggleFavorite(Request $request, Trip $trip): RedirectResponse
    {
        abort_unless($trip->status === ListingStatus::Published && $trip->verification_status === VerificationStatus::Approved, 404);
        $favorite = Favorite::query()->where('user_id', $request->user()->id)->where('trip_id', $trip->id)->first();
        $favorite ? $favorite->delete() : Favorite::query()->create(['user_id' => $request->user()->id, 'trip_id' => $trip->id]);

        return back()->with('status', $favorite ? 'Trip removed from favorites.' : 'Trip saved to favorites.');
    }

    public function notifications(Request $request): View
    {
        return view('traveler.section', ['title' => 'Notifications', 'description' => 'Booking, payment, and account updates.', 'type' => 'notifications', 'items' => Notification::query()->where('user_id', $request->user()->id)->latest()->paginate(20)]);
    }

    public function markNotificationsRead(Request $request): RedirectResponse
    {
        Notification::query()->where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', 'Notifications marked as read.');
    }

    public function review(Request $request, Booking $booking): View
    {
        abort_unless($booking->traveler_id === $request->user()->id && $booking->status === BookingStatus::Completed, 404);

        return view('traveler.review', compact('booking'));
    }

    public function storeReview(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->traveler_id === $request->user()->id && $booking->status === BookingStatus::Completed, 404);
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'communication' => ['nullable', 'integer', 'between:1,5'], 'service' => ['nullable', 'integer', 'between:1,5'], 'quality' => ['nullable', 'integer', 'between:1,5'], 'value' => ['nullable', 'integer', 'between:1,5'], 'body' => ['required', 'string', 'max:5000']]);
        Review::query()->updateOrCreate(['booking_id' => $booking->id, 'author_id' => $request->user()->id], $data + ['trip_id' => $booking->trip_id, 'vehicle_id' => $booking->vehicle_id, 'author_id' => $request->user()->id, 'booking_id' => $booking->id]);

        return redirect()->route('traveler.section', ['section' => 'reviews'])->with('status', 'Review submitted.');
    }

    public function section(Request $request, string $section): View
    {
        $data = match ($section) {
            'upcoming' => ['items' => $this->travelerBookings($request)->where('start_date', '>=', now())->latest('start_date')->paginate(20)],
            'past' => ['items' => $this->travelerBookings($request)->where('start_date', '<', now())->latest('start_date')->paginate(20)],
            'payments', 'receipts' => ['items' => $this->travelerBookings($request)->with('payments')->latest()->paginate(20)],
            'reviews' => ['items' => Review::query()->where('author_id', $request->user()->id)->with(['trip:id,name', 'vehicle:id,name'])->latest()->paginate(20)],
            'messages' => ['items' => $this->travelerBookings($request)->whereHas('conversations')->with(['conversations.messages.sender'])->latest()->paginate(20)],
            'settings' => ['items' => collect()],
            default => ['items' => collect()],
        };

        return view('traveler.section', array_merge(['title' => Str::headline($section), 'description' => 'Traveler workspace for '.Str::lower(Str::headline($section)).'.', 'type' => $section], $data));
    }

    public function compare(Request $request): View
    {
        $ids = array_slice($request->input('trips', []), 0, 3);
        $trips = Trip::query()->whereIn('id', $ids)->where('status', ListingStatus::Published)->where('verification_status', VerificationStatus::Approved)->with(['destination', 'operator.operatorProfile'])->withAvg('reviews', 'rating')->get();

        return view('traveler.compare', compact('trips'));
    }

    private function travelerBookings(Request $request): Builder
    {
        return Booking::query()->where('traveler_id', $request->user()->id);
    }
}
