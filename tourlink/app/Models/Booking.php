<?php

namespace App\Models;

use App\BookingStatus;
use App\BookingType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends TourLinkModel
{
    protected $fillable = ['reference', 'type', 'status', 'traveler_id', 'trip_id', 'vehicle_id', 'start_date', 'end_date', 'travelers', 'pickup_location', 'driver_required', 'base_amount', 'fees', 'discount', 'total_amount', 'currency', 'notes'];

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'type' => BookingType::class,
            'status' => BookingStatus::class,
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'travelers' => 'integer',
            'driver_required' => 'boolean',
            'base_amount' => 'integer',
            'fees' => 'integer',
            'discount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function traveler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traveler_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(BookingPassenger::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }
}
