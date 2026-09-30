<?php

namespace App;

enum BookingStatus: string
{
    case Pending = 'PENDING';
    case Confirmed = 'CONFIRMED';
    case Paid = 'PAID';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
    case Refunded = 'REFUNDED';
}
