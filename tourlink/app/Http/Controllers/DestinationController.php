<?php

namespace App\Http\Controllers;

use App\ListingStatus;
use App\Models\Destination;
use App\Models\Trip;
use App\VerificationStatus;
use Illuminate\View\View;

class DestinationController extends Controller
{
    public function show(Destination $destination): View
    {
        $destination->loadCount(['trips' => fn ($query) => $query
            ->where('status', ListingStatus::Published->value)
            ->where('verification_status', VerificationStatus::Approved->value)]);

        $trips = Trip::query()
            ->where('destination_id', $destination->id)
            ->where('status', ListingStatus::Published->value)
            ->where('verification_status', VerificationStatus::Approved->value)
            ->with(['destination:id,name,slug,image_url', 'category:id,name,slug', 'operator:id,name,verification_level', 'images:id,trip_id,url,alt,sort_order'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('featured')
            ->latest('created_at')
            ->paginate(9);

        return view('pages.destinations.show', compact('destination', 'trips'));
    }
}