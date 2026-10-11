<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleImage extends TourLinkModel
{
    protected $fillable = ['url', 'alt', 'sort_order', 'vehicle_id'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
