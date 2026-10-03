<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\Catalog\TripCategoryCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (TripCategoryCatalog::DEFAULT_CATEGORIES as $name) {
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
