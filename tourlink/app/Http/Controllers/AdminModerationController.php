<?php

namespace App\Http\Controllers;

use App\ListingStatus;
use App\Models\AdminLog;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\Admin\AdminDashboardMetrics;
use App\VerificationStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminModerationController extends Controller
{
    public function dashboard(AdminDashboardMetrics $metrics): View
    {
        $pendingTrips = Trip::query()
            ->with(['operator:id,name,email', 'destination:id,name'])
            ->where('verification_status', VerificationStatus::Pending)
            ->orderBy('created_at')
            ->limit(8)
            ->get();

        $pendingVehicles = Vehicle::query()
            ->with(['owner:id,name,email', 'destination:id,name'])
            ->where('verification_status', VerificationStatus::Pending)
            ->orderBy('created_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', array_merge($metrics->build(), [
            'recentActivities' => AdminLog::query()->with('admin:id,name')->latest('created_at')->limit(8)->get(),
            'pendingTrips' => $pendingTrips,
            'pendingVehicles' => $pendingVehicles,
            'pendingTripCount' => Trip::query()->where('verification_status', VerificationStatus::Pending)->count(),
            'pendingVehicleCount' => Vehicle::query()->where('verification_status', VerificationStatus::Pending)->count(),
        ]));
    }

    public function updateTrip(Request $request, Trip $trip): RedirectResponse
    {
        return $this->updateListing($request, $trip, 'Trip', 'Trip review saved.');
    }

    public function updateVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        return $this->updateListing($request, $vehicle, 'Vehicle', 'Vehicle review saved.');
    }

    private function updateListing(Request $request, Trip|Vehicle $listing, string $entity, string $message): RedirectResponse
    {
        $validated = $request->validate([
            'verification_status' => [
                'required',
                Rule::in([
                    VerificationStatus::Approved->value,
                    VerificationStatus::Rejected->value,
                    VerificationStatus::MoreInfo->value,
                ]),
            ],
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $listing, $entity, $validated): void {
            $previousStatus = $listing->verification_status->value;
            $status = VerificationStatus::from($validated['verification_status']);

            $listing->verification_status = $status;
            $listing->status = $status === VerificationStatus::Approved
                ? ListingStatus::Published
                : ListingStatus::Draft;
            $listing->save();

            $request->user()->adminLogs()->create([
                'action' => 'listing.reviewed',
                'entity' => $entity,
                'entity_id' => $listing->getKey(),
                'metadata' => [
                    'previous_status' => $previousStatus,
                    'verification_status' => $status->value,
                    'approval_note' => $validated['approval_note'] ?? null,
                ],
            ]);
        });

        return back()->with('status', $message);
    }
}
