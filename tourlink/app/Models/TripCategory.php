<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class TripCategory extends TourLinkModel
{
    protected $fillable = ['name', 'slug'];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'category_id');
    }
}
