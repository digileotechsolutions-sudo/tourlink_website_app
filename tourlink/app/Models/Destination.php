<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends TourLinkModel
{
    protected $fillable = ['name', 'slug', 'country', 'description', 'image_url', 'location', 'attractions', 'activities'];

    protected function casts(): array
    {
        return [
            'attractions' => 'array',
            'activities' => 'array',
        ];
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
