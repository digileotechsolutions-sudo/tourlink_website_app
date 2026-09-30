<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPassenger extends TourLinkModel
{
    protected $fillable = ['booking_id', 'name', 'email', 'phone'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
