<?php

namespace App\Models;

class Event extends TourLinkModel
{
    protected $fillable = ['title', 'slug', 'description', 'image_url', 'starts_at', 'location', 'published'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'published' => 'boolean',
        ];
    }
}
