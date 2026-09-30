<?php

namespace App;

enum VerificationStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case MoreInfo = 'MORE_INFO';
}
