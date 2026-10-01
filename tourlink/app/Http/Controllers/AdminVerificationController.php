<?php

namespace App\Http\Controllers;

use App\ListingStatus;
use App\Models\AdminLog;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VerificationRequest;
use App\Services\Referral\ReferralService;
use App\VerificationLevel;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(VerificationStatus::class)],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $requests = VerificationRequest::query()
            ->with([
                'user:id,name,email,phone,role,verification_level',
                'vehicle:id,registration_number,make,model,year,seating_capacity,body_type,owner_id',
            ])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->whereHas('user', fn (Builder $user): Builder => $user
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')))
            ->when(! isset($filters['status']), fn (Builder $query): Builder => $query->whereIn('status', [VerificationStatus::Pending->value, VerificationStatus::MoreInfo->value]))
            ->orderByRaw("CASE WHEN status = 'PENDING' THEN 0 ELSE 1 END")
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.verification.index', compact('requests', 'filters'));
    }

    public function update(Request $request, VerificationRequest $verificationRequest, ReferralService $referrals): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                VerificationStatus::Approved->value,
                VerificationStatus::Rejected->value,
                VerificationStatus::MoreInfo->value,
            ])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $published = ['trips' => 0, 'vehicles' => 0];
        $reviewedUserId = null;
        $reviewStatus = VerificationStatus::from($data['status']);

        $requiredDocuments = match ($verificationRequest->type) {
            'OPERATOR' => ['registration_certificate', 'kra_pin', 'tour_operator_license', 'business_permit', 'representative_id'],
            'VEHICLE_OWNER_IDENTITY' => ['owner_id'],
            'VEHICLE' => ['logbook', 'insurance', 'front_photo', 'rear_photo', 'left_photo', 'right_photo', 'interior_photo', 'number_plate_photo'],
            default => [],
        };

        if ($verificationRequest->type === 'VEHICLE') {
            abort_unless($verificationRequest->vehicle !== null && $verificationRequest->vehicle->owner_id === $verificationRequest->user_id, 404);
        }

        if ($reviewStatus === VerificationStatus::Approved && $requiredDocuments !== []) {
            $documents = collect($verificationRequest->documents ?? [])->keyBy('key');
            $missingDocuments = [];

            foreach ($requiredDocuments as $key) {
                $path = $documents->get($key)['path'] ?? null;
                if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
                    $missingDocuments[] = str($key)->replace('_', ' ')->toString();
                }
            }

            if ($missingDocuments !== []) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot approve this request. Missing or unavailable documents: '.implode(', ', $missingDocuments).'.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $verificationRequest, $data, &$published, &$reviewedUserId): void {
            $verificationRequest = VerificationRequest::query()->lockForUpdate()->findOrFail($verificationRequest->id);
            $previousStatus = $verificationRequest->status->value;
            $status = VerificationStatus::from($data['status']);
            $verificationRequest->update([
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            $user = $verificationRequest->user;
            $reviewedUserId = $user->id;

            if ($status === VerificationStatus::Approved) {
                if ($verificationRequest->type !== 'VEHICLE' && $user->verification_level === VerificationLevel::Basic) {
                    $user->forceFill(['verification_level' => VerificationLevel::Verified])->save();
                }

                if ($verificationRequest->type === 'VEHICLE' && $verificationRequest->vehicle_id) {
                    $published['vehicles'] = Vehicle::query()
                        ->whereKey($verificationRequest->vehicle_id)
                        ->where('owner_id', $user->id)
                        ->where('verification_status', VerificationStatus::Pending)
                        ->update([
                            'verification_status' => VerificationStatus::Approved,
                            'status' => ListingStatus::Published,
                        ]);
                } elseif (in_array($verificationRequest->type, ['OPERATOR', 'VEHICLE_OWNER'], true)) {
                    $published = $this->publishPendingListings($user);
                }
            }

            $user->appNotifications()->create([
                'title' => 'Verification request reviewed',
                'body' => ($data['notes'] ?? null) ?: 'Your verification request was '.strtolower(str_replace('_', ' ', $status->value)).'.',
                'type' => 'VERIFICATION',
            ]);

            AdminLog::query()->create([
                'admin_id' => $request->user()->id,
                'action' => 'verification.reviewed',
                'entity' => 'VerificationRequest',
                'entity_id' => $verificationRequest->id,
                'metadata' => [
                    'user_id' => $user->id,
                    'previous_status' => $previousStatus,
                    'status' => $status->value,
                    'notes' => $data['notes'] ?? null,
                    'published_listings' => $published,
                ],
            ]);
        });

        // Outside the transaction so the reward write cannot extend the lock,
        // and only when the account itself was approved. This is the second of
        // the two admin approval paths that must move referrals forward.
        if ($reviewedUserId !== null) {
            $reviewedUser = User::query()->find($reviewedUserId);

            if ($reviewedUser) {
                if ($reviewStatus === VerificationStatus::Approved) {
                    $referrals->markApproved($reviewedUser);
                } elseif ($reviewStatus === VerificationStatus::Rejected) {
                    $referrals->markRejected($reviewedUser);
                }
            }
        }

        $publishedCount = array_sum($published);

        return back()->with('status', $publishedCount > 0
            ? "Verification request reviewed. {$publishedCount} listing(s) are now live on the public pages."
            : 'Verification request reviewed.');
    }

    /**
     * Serve a compliance document to the reviewing admin. The files are private
     * to the local disk, so this is the only path that can read them.
     */
    public function document(VerificationRequest $verificationRequest, string $document): StreamedResponse
    {
        $stored = collect($verificationRequest->documents ?? [])->firstWhere('key', $document);
        $stored = is_array($stored) ? $stored : [];
        $path = $stored['path'] ?? null;

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response(
            $path,
            $stored['original_name'] ?? $document,
            ['Content-Type' => $stored['mime_type'] ?? 'application/octet-stream'],
            'inline'
        );
    }

    /**
     * Approving the account is the only approval a provider ever sees, so their
     * listings have to follow it. Otherwise they stay PENDING and the public
     * queries, which require PUBLISHED and APPROVED, keep hiding them.
     *
     * @return array{trips: int, vehicles: int}
     */
    private function publishPendingListings(User $user): array
    {
        $published = [
            'verification_status' => VerificationStatus::Approved,
            'status' => ListingStatus::Published,
        ];

        return [
            'trips' => Trip::query()
                ->where('operator_id', $user->id)
                ->where('verification_status', VerificationStatus::Pending)
                ->update($published),
            'vehicles' => Vehicle::query()
                ->where('owner_id', $user->id)
                ->where('verification_status', VerificationStatus::Pending)
                ->update($published),
        ];
    }
}
