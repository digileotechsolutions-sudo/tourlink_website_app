<?php

namespace App\Models;

use App\ListingStatus;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends TourLinkModel
{
    protected $fillable = ['slug', 'name', 'description', 'starting_point', 'ending_point', 'duration_days', 'departure_date', 'return_date', 'price_per_person', 'max_travelers', 'min_travelers', 'available_seats', 'pickup_points', 'itinerary', 'accommodation', 'meals', 'transport', 'activities', 'included_items', 'excluded_items', 'cancellation_policy', 'status', 'featured', 'verification_status', 'operator_id', 'destination_id', 'category_id', 'required_vehicle_id'];

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'departure_date' => 'datetime',
            'return_date' => 'datetime',
            'price_per_person' => 'integer',
            'max_travelers' => 'integer',
            'min_travelers' => 'integer',
            'available_seats' => 'integer',
            'pickup_points' => 'array',
            'itinerary' => 'array',
            'meals' => 'array',
            'activities' => 'array',
            'included_items' => 'array',
            'excluded_items' => 'array',
            'status' => ListingStatus::class,
            'featured' => 'boolean',
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TripCategory::class, 'category_id');
    }

    public function requiredVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'required_vehicle_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(TripImage::class)->orderBy('sort_order');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(TripAvailability::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('hidden_by_admin', false);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
