<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAvailability extends TourLinkModel
{
    protected $fillable = ['vehicle_id', 'date', 'available'];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'available' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
