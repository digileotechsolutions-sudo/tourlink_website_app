<?php

namespace App\Http\Controllers;

use App\AccountStatus;
use App\BookingStatus;
use App\ListingStatus;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredTrips = Trip::query()
            ->where('status', ListingStatus::Published->value)
            ->where('verification_status', VerificationStatus::Approved->value)
            ->with(['destination:id,name,slug,image_url', 'category:id,name,slug', 'operator:id,name,verification_level', 'images:id,trip_id,url,alt,sort_order'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('featured')
            ->latest('created_at')
            ->limit(3)
            ->get();

        $destinations = Destination::query()
            ->withCount(['trips' => fn (Builder $query): Builder => $query->where('status', ListingStatus::Published->value)])
            ->orderByDesc('trips_count')
            ->orderBy('name')
            ->limit(4)
            ->get();

        $vehicles = Vehicle::query()
            ->where('status', ListingStatus::Published->value)
            ->where('verification_status', VerificationStatus::Approved->value)
            ->with(['owner:id,name,verification_level', 'images:id,vehicle_id,url,alt'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest('created_at')
            ->limit(3)
            ->get();

        $reviews = Review::query()
            ->where('hidden_by_admin', false)
            ->with(['author:id,name', 'trip:id,name,slug', 'vehicle:id,name,slug'])
            ->latest('created_at')
            ->limit(3)
            ->get();

        $journeyCount = Booking::query()
            ->whereNotIn('status', [BookingStatus::Cancelled->value, BookingStatus::Refunded->value])
            ->count();

        $providerCount = User::query()
            ->whereIn('role', ['OPERATOR', 'VEHICLE_OWNER'])
            ->where('account_status', AccountStatus::Active->value)
            ->count();

        return view('pages.home', compact('featuredTrips', 'destinations', 'vehicles', 'reviews', 'journeyCount', 'providerCount'));
    }
}
