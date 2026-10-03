<?php

namespace App\Http\Controllers;

use App\ListingStatus;
use App\Models\Destination;
use App\Models\Trip;
use App\Services\Catalog\TripCategoryCatalog;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TripController extends Controller
{
    public function index(Request $request, TripCategoryCatalog $categoryCatalog): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'destination' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'maxPrice' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'minRating' => ['nullable', 'numeric', 'between:0,5'],
            'sort' => ['nullable', Rule::in(['price', 'rating'])],
        ]);

        $trips = Trip::query()
            ->where('status', ListingStatus::Published->value)
            ->where('verification_status', VerificationStatus::Approved->value)
            ->with(['destination:id,name,slug,image_url', 'category:id,name,slug', 'operator:id,name,verification_level', 'images:id,trip_id,url,alt,sort_order'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $trips->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('starting_point', 'like', '%'.$search.'%')
                    ->orWhere('ending_point', 'like', '%'.$search.'%')
                    ->orWhereHas('destination', fn (Builder $destination): Builder => $destination->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('category', fn (Builder $category): Builder => $category->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('operator', fn (Builder $operator): Builder => $operator->where('name', 'like', '%'.$search.'%'));
            });
        }

        if (! empty($filters['destination'])) {
            $destination = $filters['destination'];
            $trips->whereHas('destination', fn (Builder $query): Builder => $query
                ->where('slug', $destination)
                ->orWhere('name', $destination));
        }

        if (! empty($filters['category'])) {
            $category = $filters['category'];
            $trips->whereHas('category', fn (Builder $query): Builder => $query
                ->where('slug', $category)
                ->orWhere('name', $category));
        }

        if (isset($filters['maxPrice'])) {
            $trips->where('price_per_person', '<=', $filters['maxPrice']);
        }

        if (isset($filters['minRating'])) {
            $trips->having('reviews_avg_rating', '>=', $filters['minRating']);
        }

        match ($filters['sort'] ?? null) {
            'price' => $trips->orderBy('price_per_person'),
            'rating' => $trips->orderByDesc('reviews_avg_rating'),
            default => $trips->orderByDesc('featured'),
        };

        $results = $trips->latest('created_at')->paginate(9)->withQueryString();
        $destinations = Destination::query()->orderBy('name')->get(['id', 'name', 'slug']);
        $categories = $categoryCatalog->all();

        return view('pages.trips.index', [
            'trips' => $results,
            'filters' => $filters,
            'destinations' => $destinations,
            'categories' => $categories,
        ]);
    }

    public function show(Trip $trip): View
    {
        abort_unless(
            $trip->status === ListingStatus::Published && $trip->verification_status === VerificationStatus::Approved,
            404,
        );

        $trip->load(['destination', 'category', 'operator.operatorProfile', 'images', 'requiredVehicle']);
        $trip->loadAvg('reviews', 'rating');
        $trip->loadCount('reviews');

        return view('pages.trips.show', compact('trip'));
    }
}
