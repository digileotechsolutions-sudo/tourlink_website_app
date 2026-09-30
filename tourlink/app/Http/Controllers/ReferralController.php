<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\ReferralStatus;
use App\Services\Referral\ReferralService;
use App\Services\Referral\ReferralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralService $referrals,
        private readonly ReferralSettings $settings,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $code = $this->referrals->ensureCodeFor($user);
        $link = $this->referrals->shareUrlFor($user);
        $statistics = $this->referrals->statisticsFor($user);

        $history = Referral::query()
            ->where('referrer_id', $user->id)
            ->with('referredUser:id,name,email,role,approval_status,account_status,created_at')
            ->latest('created_at')
            ->paginate(20);

        return view('referrals.index', [
            'referralCode' => $code,
            'referralLink' => $link,
            'statistics' => $statistics,
            'history' => $history,
            'rewards' => $this->settings->rewardEnabled()
                ? [
                    'currency' => $this->settings->rewardCurrency(),
                    'amount' => $this->settings->rewardAmount(),
                    'requires_approval' => $this->settings->rewardRequiresApproval(),
                    'trigger' => $this->settings->rewardTrigger(),
                ]
                : null,
            'referredBy' => $user->referral?->referrer?->only(['name', 'email']),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return Collection::make(ReferralStatus::cases())
            ->mapWithKeys(fn (ReferralStatus $status): array => [$status->value => str($status->value)->replace('_', ' ')->title()])
            ->all();
    }
}
