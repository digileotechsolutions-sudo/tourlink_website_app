<?php

namespace App\Models;

class Payout extends TourLinkModel
{
    protected $fillable = ['provider_id', 'amount', 'status', 'reference'];

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
