<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleImage extends TourLinkModel
{
    protected $fillable = ['url', 'alt', 'sort_order', 'vehicle_id'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    protected function url(): Attribute
    {
        return Attribute::get(static function (?string $value): ?string {
            $path = is_string($value) ? parse_url($value, PHP_URL_PATH) : null;

            return is_string($path) && preg_match('~^/(?:storage|media)/vehicle-images/[A-Za-z0-9_-][A-Za-z0-9._-]*$~D', $path) === 1
                ? (preg_replace('~^/storage/~', '/media/', $path) ?? $path)
                : $value;
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
