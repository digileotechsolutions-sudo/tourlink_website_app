<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripAvailability extends TourLinkModel
{
    protected $fillable = ['trip_id', 'date', 'seats'];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'seats' => 'integer',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
