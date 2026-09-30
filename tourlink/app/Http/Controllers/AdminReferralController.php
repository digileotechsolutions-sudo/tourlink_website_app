<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReferralSettingsRequest;
use App\Models\Referral;
use App\Models\Setting;
use App\ReferralRewardStatus;
use App\ReferralStatus;
use App\Services\Referral\ReferralSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminReferralController extends Controller
{
    public function __construct(private readonly ReferralSettings $settings) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:30'],
            'reward_status' => ['nullable', 'string', 'max:30'],
        ]);

        $referrals = Referral::query()
            ->with(['referrer:id,name,email,referral_code', 'referredUser:id,name,email,approval_status'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('referral_code', 'like', '%'.$search.'%')
                        ->orWhereHas('referrer', fn (Builder $q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('referredUser', fn (Builder $q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['reward_status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('reward_status', $status))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.referrals.index', [
            'referrals' => $referrals,
            'filters' => $filters,
            'statistics' => $this->globalStatistics(),
            'statuses' => ReferralStatus::cases(),
            'rewardStatuses' => ReferralRewardStatus::cases(),
            'rewardTriggers' => ReferralSettingsRequest::rewardTriggers(),
            'settings' => $this->settingsPayload(),
        ]);
    }

    public function updateSettings(ReferralSettingsRequest $request): RedirectResponse
    {
        $payload = $request->referralSettingsPayload();

        DB::transaction(function () use ($request, $payload): void {
            foreach ($payload as $key => $value) {
                // Settings are stored and read under the "referral." namespace,
                // so the prefix must be added here to match ReferralSettings.
                Setting::query()->updateOrCreate(['key' => 'referral.'.$key], ['value' => $value]);
            }

            $request->user()->adminLogs()->create([
                'action' => 'referral.settings_updated',
                'entity' => 'Setting',
                'entity_id' => null,
                'metadata' => ['settings' => $payload],
            ]);
        });

        $this->settings->flush();

        return back()->with('status', 'Referral settings updated.');
    }

    /**
     * Releases or refuses a reward that was queued for manual approval.
     */
    public function reward(Request $request, Referral $referral): RedirectResponse
    {
        $data = $request->validate([
            'reward_status' => ['required', 'in:APPROVED,REJECTED'],
        ]);

        $status = $data['reward_status'];
        $handled = false;

        DB::transaction(function () use ($request, $referral, $status, &$handled): void {
            $locked = Referral::query()->lockForUpdate()->findOrFail($referral->id);

            // Only a reward awaiting review can be decided, so a repeated click
            // can never pay the same referral twice.
            if ($locked->reward_status !== ReferralRewardStatus::Pending) {
                return;
            }

            $handled = true;

            if ($status === 'APPROVED') {
                $locked->forceFill([
                    'reward_status' => ReferralRewardStatus::Paid,
                    'rewarded_at' => now(),
                    'status' => ReferralStatus::Rewarded,
                ])->save();
            } else {
                $locked->forceFill(['reward_status' => ReferralRewardStatus::Rejected])->save();
            }

            $request->user()->adminLogs()->create([
                'action' => 'referral.reward_'.strtolower($status),
                'entity' => 'Referral',
                'entity_id' => $locked->id,
                'metadata' => [
                    'referrer_id' => $locked->referrer_id,
                    'referred_user_id' => $locked->referred_user_id,
                    'reward_amount' => $locked->reward_amount,
                ],
            ]);
        });

        if (! $handled) {
            return back()->with('status', 'That reward has already been reviewed.');
        }

        return back()->with('status', $status === 'APPROVED' ? 'Referral reward released.' : 'Referral reward rejected.');
    }

    /**
     * @return array<string, int>
     */
    private function globalStatistics(): array
    {
        return [
            'total' => Referral::query()->count(),
            'pending' => Referral::query()->where('status', ReferralStatus::Pending)->count(),
            'approved' => Referral::query()->whereIn('status', [ReferralStatus::Approved, ReferralStatus::Rewarded])->count(),
            'rewarded' => Referral::query()->where('status', ReferralStatus::Rewarded)->count(),
            'pending_rewards' => Referral::query()->where('reward_status', ReferralRewardStatus::Pending)->count(),
            'rewards_paid' => (int) Referral::query()->where('reward_status', ReferralRewardStatus::Paid)->sum('reward_amount'),
        ];
    }

    /**
     * @return array<string, string|int|bool|ReferralStatus>
     */
    private function settingsPayload(): array
    {
        return [
            'enabled' => $this->settings->enabled(),
            'reward_enabled' => $this->settings->rewardEnabled(),
            'reward_amount' => $this->settings->rewardAmount(),
            'reward_currency' => $this->settings->rewardCurrency(),
            'reward_trigger' => $this->settings->rewardTrigger(),
            'reward_requires_approval' => $this->settings->rewardRequiresApproval(),
            'code_prefix' => $this->settings->codePrefix(),
            'code_length' => $this->settings->codeLength(),
        ];
    }
}
