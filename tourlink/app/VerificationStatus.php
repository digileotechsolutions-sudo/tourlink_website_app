<?php

namespace App;

enum VerificationStatus: string
{
    case Pending = 'PENDING';
    case UnderReview = 'UNDER_REVIEW';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case MoreInfo = 'MORE_INFO';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '🟡 Pending Verification',
            self::UnderReview => '🔵 Under Review',
            self::Approved => '🟢 Verified',
            self::Rejected => '🔴 Rejected',
            self::MoreInfo => '⚠️ Documents Required',
        };
    }
}
