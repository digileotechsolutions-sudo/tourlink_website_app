<?php

namespace App\Models;

class Setting extends TourLinkModel
{
    protected $fillable = ['key', 'value'];

    public const CREATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return ['updated_at' => 'datetime'];
    }
}
