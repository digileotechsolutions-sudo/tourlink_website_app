<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends TourLinkModel
{
    protected $fillable = ['booking_id', 'author_id', 'trip_id', 'vehicle_id', 'rating', 'communication', 'service', 'quality', 'value', 'body', 'hidden_by_admin'];

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'communication' => 'integer',
            'service' => 'integer',
            'quality' => 'integer',
            'value' => 'integer',
            'hidden_by_admin' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
