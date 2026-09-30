<?php

namespace App;

enum ReferralRewardStatus: string
{
    case None = 'NONE';
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Paid = 'PAID';
    case Rejected = 'REJECTED';
}
