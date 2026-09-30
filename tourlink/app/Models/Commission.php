<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends TourLinkModel
{
    protected $fillable = ['booking_id', 'rate', 'gross_amount', 'commission_amount', 'provider_amount', 'payout_status'];

    protected function casts(): array
    {
        return [
            'rate' => 'float',
            'gross_amount' => 'integer',
            'commission_amount' => 'integer',
            'provider_amount' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
