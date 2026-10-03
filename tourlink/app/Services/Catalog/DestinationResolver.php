<?php

namespace App\Services\Catalog;

use App\Models\Destination;
use Illuminate\Support\Str;

class DestinationResolver
{
    public function resolveOrCreate(string $name): Destination
    {
        $name = trim($name);
        $existing = Destination::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return $existing;
        }

        $baseSlug = Str::slug($name) ?: 'destination';
        $slug = $baseSlug;
        $suffix = 2;

        while (Destination::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return Destination::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'country' => 'Kenya',
                'description' => "Explore {$name}, Kenya, with local tour and travel providers on Havenedge Tourlink.",
                'image_url' => asset('form-image1.png'),
                'attractions' => [],
                'activities' => [],
            ],
        );
    }
}
