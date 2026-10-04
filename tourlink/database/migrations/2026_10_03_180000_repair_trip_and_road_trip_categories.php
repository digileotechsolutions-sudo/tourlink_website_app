<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const CATEGORIES = [
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

    public function up(): void
    {
        foreach (self::CATEGORIES as $name) {
            $slug = Str::slug($name);
            $id = DB::table('trip_categories')->where('slug', $slug)->value('id') ?? (string) Str::ulid();

            DB::table('trip_categories')->updateOrInsert(
                ['slug' => $slug],
                [
                    'id' => $id,
                    'name' => $name,
                    'slug' => $slug,
                ],
            );
        }
    }

    public function down(): void
    {
        // Keep catalog entries because trips may already reference them.
    }
};
