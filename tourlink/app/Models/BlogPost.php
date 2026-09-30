<?php

namespace App\Models;

class BlogPost extends TourLinkModel
{
    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'image_url', 'published'];

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
