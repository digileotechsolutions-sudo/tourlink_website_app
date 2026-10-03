<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const CATEGORIES = [
        'Safari & Wildlife 🦁',
        'Beach & Coastal Tours 🏖️',
        'Mountain & Hiking ⛰️',
        'Cultural & Heritage Tours 🏛️',
        'City Tours 🏙️',
        'Nature & Adventure 🌿',
        'Food & Culinary Tours 🍽️',
        'Photography Tours 📸',
        'Family Tours 👨‍👩‍👧‍👦',
        'Luxury Tours ✨',
        'Budget Tours 🎒',
        'Camping & Outdoor ⛺',
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

            if (DB::table('trip_categories')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('trip_categories')->insert([
                'id' => (string) Str::ulid(),
                'name' => $name,
                'slug' => $slug,
            ]);
        }
    }

    public function down(): void
    {
        // Keep catalog entries on rollback because trips may already reference them.
    }
};
