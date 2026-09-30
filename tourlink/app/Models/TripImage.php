<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripImage extends TourLinkModel
{
    protected $fillable = ['url', 'alt', 'sort_order', 'trip_id'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
