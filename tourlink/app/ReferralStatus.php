<?php

namespace App;

enum ReferralStatus: string
{
    case Pending = 'PENDING';
    case Verified = 'VERIFIED';
    case Approved = 'APPROVED';
    case Rewarded = 'REWARDED';
    case Rejected = 'REJECTED';

    /**
     * Statuses from which a referral can no longer move.
     *
     * @return array<int, self>
     */
    public function terminal(): array
    {
        return [self::Rewarded, self::Rejected];
    }

    public function isTerminal(): bool
    {
        return in_array($this, $this->terminal(), true);
    }
}
