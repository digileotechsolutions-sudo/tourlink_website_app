<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelerProfile extends TourLinkModel
{
    protected $fillable = ['user_id', 'bio', 'preferred_currency'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
