<?php

namespace App\Http\Controllers;

use App\ListingStatus;
use App\Models\Vehicle;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $vehicles = Vehicle::query()
            ->where('status', ListingStatus::Published->value)
            ->where('verification_status', VerificationStatus::Approved->value)
            ->with(['owner:id,name,verification_level', 'destination:id,name,slug', 'images:id,vehicle_id,url,alt'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $vehicles->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('make', 'like', '%'.$search.'%')
                    ->orWhere('model', 'like', '%'.$search.'%')
                    ->orWhere('body_type', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        return view('pages.vehicles.index', [
            'vehicles' => $vehicles->latest('created_at')->paginate(9)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Vehicle $vehicle): View
    {
        abort_unless(
            $vehicle->status === ListingStatus::Published && $vehicle->verification_status === VerificationStatus::Approved,
            404,
        );

        $vehicle->load(['owner.vehicleOwnerProfile', 'destination', 'images', 'availabilities']);
        $vehicle->loadAvg('reviews', 'rating');
        $vehicle->loadCount('reviews');

        return view('pages.vehicles.show', compact('vehicle'));
    }
}
