<?php

namespace App\Services\Catalog;

use App\Models\TripCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TripCategoryCatalog
{
    public const DEFAULT_CATEGORIES = [
        'Safari & Wildlife',
        'Beach & Coastal Tours',
        'Mountain & Hiking',
        'Cultural & Heritage Tours',
        'City Tours',
        'Nature & Adventure',
        'Food & Culinary Tours',
        'Photography Tours',
        'Family Tours',
        'Luxury Tours',
        'Budget Tours',
        'Camping & Outdoor',
        'Weekend Getaways',
        'Scenic Drives',
        'Safari Road Trips',
        'Coastal Road Trips',
        'Mountain Road Trips',
        'National Park Trips',
        'Camping Road Trips',
        'Adventure Road Trips',
        'Group Road Trips',
        'Family Road Trips',
        'Motorbike Trips',
        'Cross-Country Trips',
    ];

    public function all(): Collection
    {
        foreach (self::DEFAULT_CATEGORIES as $name) {
            TripCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }

        return TripCategory::query()->orderBy('name')->get();
    }
}
