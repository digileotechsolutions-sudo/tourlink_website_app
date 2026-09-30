<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleOwnerProfile extends TourLinkModel
{
    protected $fillable = ['user_id', 'business_name', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
