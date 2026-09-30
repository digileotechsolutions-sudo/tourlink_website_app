<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\VerificationRequest;
use App\VerificationLevel;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(VerificationStatus::class)],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $requests = VerificationRequest::query()
            ->with('user:id,name,email,phone,role,verification_level')
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

    public function update(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                VerificationStatus::Approved->value,
                VerificationStatus::Rejected->value,
                VerificationStatus::MoreInfo->value,
            ])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $verificationRequest, $data): void {
            $verificationRequest = VerificationRequest::query()->lockForUpdate()->findOrFail($verificationRequest->id);
            $previousStatus = $verificationRequest->status->value;
            $status = VerificationStatus::from($data['status']);
            $verificationRequest->update([
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            $user = $verificationRequest->user;
            if ($status === VerificationStatus::Approved && $user->verification_level === VerificationLevel::Basic) {
                $user->forceFill(['verification_level' => VerificationLevel::Verified])->save();
            }

            $user->appNotifications()->create([
                'title' => 'Verification request reviewed',
                'body' => $data['notes'] ?: 'Your verification request was '.strtolower(str_replace('_', ' ', $status->value)).'.',
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
                ],
            ]);
        });

        return back()->with('status', 'Verification request reviewed.');
    }
}
