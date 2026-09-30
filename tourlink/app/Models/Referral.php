<?php

namespace App\Models;

use App\ReferralRewardStatus;
use App\ReferralStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['referrer_id', 'referred_user_id', 'referral_code', 'status', 'reward_status', 'reward_amount', 'verified_at', 'approved_at', 'rewarded_at'])]
class Referral extends TourLinkModel
{
    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'reward_status' => ReferralRewardStatus::class,
            'reward_amount' => 'integer',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'rewarded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * Only REWARDED and REJECTED are final; a referral can otherwise still be
     * promoted as the referred account clears verification and approval.
     */
    public function isSettled(): bool
    {
        return $this->status->isTerminal();
    }
}
