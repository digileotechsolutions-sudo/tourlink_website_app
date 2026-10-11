<?php

namespace App\Models;

use App\ListingStatus;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends TourLinkModel
{
    protected $fillable = ['slug', 'name', 'registration_number', 'make', 'model', 'year', 'body_type', 'engine_cc', 'colour', 'seating_capacity', 'tare_weight', 'axles', 'load_capacity', 'transmission', 'fuel_type', 'air_conditioning', 'four_by_four', 'driver_included', 'price_per_day', 'hourly_price', 'trip_price', 'driver_charge', 'additional_charges', 'currency', 'service_areas', 'location', 'status', 'verification_status', 'owner_id', 'destination_id', 'submitted_at'];

    public const UPDATED_AT = null;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'engine_cc' => 'integer',
            'seating_capacity' => 'integer',
            'tare_weight' => 'integer',
            'axles' => 'integer',
            'load_capacity' => 'integer',
            'air_conditioning' => 'boolean',
            'four_by_four' => 'boolean',
            'driver_included' => 'boolean',
            'price_per_day' => 'integer',
            'hourly_price' => 'integer',
            'trip_price' => 'integer',
            'driver_charge' => 'integer',
            'additional_charges' => 'array',
            'service_areas' => 'array',
            'status' => ListingStatus::class,
            'verification_status' => VerificationStatus::class,
            'created_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(VehicleAvailability::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('hidden_by_admin', false);
    }

    public function requiredTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'required_vehicle_id');
    }
}
