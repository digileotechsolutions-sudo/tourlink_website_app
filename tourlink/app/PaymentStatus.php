<?php

namespace App;

enum PaymentStatus: string
{
    case Pending = 'PENDING';
    case Processing = 'PROCESSING';
    case Successful = 'SUCCESSFUL';
    case Failed = 'FAILED';
    case Cancelled = 'CANCELLED';
    case Refunded = 'REFUNDED';
    case PartiallyRefunded = 'PARTIALLY_REFUNDED';
}
