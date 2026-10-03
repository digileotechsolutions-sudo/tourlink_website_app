<?php

namespace App\Http\Controllers;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\ListingStatus;
use App\Models\Trip;
use App\Models\TripCategory;
use App\Models\User;
use App\Models\Vehicle;
use App\Role;
use App\Services\Catalog\DestinationResolver;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class AdminTripController extends Controller
{
    private const TEXT_LIST_FIELDS = [
        'pickup_points',
        'meals',
        'activities',
        'included_items',
        'excluded_items',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_column(ListingStatus::cases(), 'value'))],
            'verification_status' => ['nullable', Rule::in(array_values(array_diff(array_column(VerificationStatus::cases(), 'value'), [VerificationStatus::UnderReview->value])))],
        ]);

        $trips = Trip::query()
            ->with(['operator:id,name', 'destination:id,name', 'category:id,name'])
            ->withCount('bookings')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['verification_status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('verification_status', $status))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.trips.index', compact('trips', 'filters'));
    }

    public function create(): View
    {
        return view('admin.trips.form', $this->formData(new Trip));
    }

    public function store(Request $request, DestinationResolver $destinationResolver): RedirectResponse
    {
        $data = $this->validatedTripData($request);
        $images = $data['images'] ?? [];
        unset($data['images']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name']);

        $trip = DB::transaction(function () use ($request, $data, $images, $destinationResolver): Trip {
            $data['destination_id'] = $destinationResolver->resolveOrCreate($data['destination_name'])->id;
            unset($data['destination_name']);
            $trip = Trip::query()->create($data);
            $this->syncImages($trip, $images);
            $this->writeAuditLog($request, $trip, 'trip.created', array_keys($data), count($images));

            return $trip;
        });

        return redirect()->route('admin.trips.edit', $trip)->with('status', 'Trip created.');
    }

    public function edit(Trip $trip): View
    {
        $trip->load('images');

        return view('admin.trips.form', $this->formData($trip));
    }

    public function update(Request $request, Trip $trip, DestinationResolver $destinationResolver): RedirectResponse
    {
        $data = $this->validatedTripData($request, $trip);
        $images = $data['images'] ?? [];
        unset($data['images']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], $trip);

        DB::transaction(function () use ($request, $trip, $data, $images, $destinationResolver): void {
            $data['destination_id'] = $destinationResolver->resolveOrCreate($data['destination_name'])->id;
            unset($data['destination_name']);
            $trip->fill($data)->save();
            $this->syncImages($trip, $images);
            $this->writeAuditLog($request, $trip, 'trip.updated', array_keys($data), count($images));
        });

        return redirect()->route('admin.trips.edit', $trip)->with('status', 'Trip updated.');
    }

    public function archive(Request $request, Trip $trip): RedirectResponse
    {
        DB::transaction(function () use ($request, $trip): void {
            $trip->update(['status' => ListingStatus::Archived]);
            $this->writeAuditLog($request, $trip, 'trip.archived', ['status'], $trip->images()->count());
        });

        return redirect()->route('admin.trips.index')->with('status', 'Trip archived. Booking history was preserved.');
    }

    private function validatedTripData(Request $request, ?Trip $trip = null): array
    {
        $this->normalizeFields($request);
        $request->merge(['featured' => $request->boolean('featured')]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'description' => ['required', 'string', 'max:20000'],
            'starting_point' => ['required', 'string', 'max:255'],
            'ending_point' => ['required', 'string', 'max:255'],
            'destination_name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'string', 'exists:trip_categories,id'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'departure_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after:departure_date'],
            'price_per_person' => ['required', 'integer', 'min:0', 'max:100000000'],
            'max_travelers' => ['required', 'integer', 'min:1', 'max:10000'],
            'min_travelers' => ['required', 'integer', 'min:1', 'lte:max_travelers'],
            'available_seats' => ['required', 'integer', 'min:0', 'lte:max_travelers'],
            'pickup_points' => ['required', 'array', 'min:1'],
            'pickup_points.*' => ['required', 'string', 'max:255'],
            'itinerary' => ['required', 'array', 'min:1'],
            'itinerary.*.day' => ['required', 'integer', 'min:1'],
            'itinerary.*.title' => ['required', 'string', 'max:120'],
            'itinerary.*.detail' => ['required', 'string', 'max:4000'],
            'accommodation' => ['nullable', 'string', 'max:5000'],
            'meals' => ['nullable', 'array'],
            'meals.*' => ['required', 'string', 'max:255'],
            'transport' => ['nullable', 'string', 'max:5000'],
            'activities' => ['nullable', 'array'],
            'activities.*' => ['required', 'string', 'max:255'],
            'included_items' => ['nullable', 'array'],
            'included_items.*' => ['required', 'string', 'max:255'],
            'excluded_items' => ['nullable', 'array'],
            'excluded_items.*' => ['required', 'string', 'max:255'],
            'cancellation_policy' => ['required', 'string', 'max:10000'],
            'operator_id' => ['required', 'string', Rule::exists('users', 'id')->where('role', Role::Operator->value)->where('account_status', AccountStatus::Active->value)->where('approval_status', AccountApprovalStatus::Approved->value)],
            'required_vehicle_id' => ['nullable', 'string', 'exists:vehicles,id'],
            'status' => ['required', Rule::in(array_column(ListingStatus::cases(), 'value'))],
            'featured' => ['required', 'boolean'],
            'verification_status' => ['required', Rule::in(array_values(array_diff(array_column(VerificationStatus::cases(), 'value'), [VerificationStatus::UnderReview->value])))],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*.url' => ['required', 'url:http,https', 'max:2048'],
            'images.*.alt' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['status'] === ListingStatus::Published->value && $data['verification_status'] !== VerificationStatus::Approved->value) {
            throw ValidationException::withMessages(['status' => 'A trip must be approved before it can be published.']);
        }

        $reservedSeats = $trip?->bookings()
            ->whereNotIn('status', ['CANCELLED', 'REFUNDED', 'COMPLETED'])
            ->sum('travelers') ?? 0;

        if ($data['max_travelers'] < $data['available_seats'] + $reservedSeats) {
            throw ValidationException::withMessages(['available_seats' => 'Available seats plus active bookings cannot exceed maximum travelers.']);
        }

        return $data;
    }

    private function normalizeFields(Request $request): void
    {
        $normalized = [];

        foreach (self::TEXT_LIST_FIELDS as $field) {
            $value = $request->input($field, []);
            if (is_string($value)) {
                $value = preg_split('/\R/u', $value) ?: [];
            }

            $normalized[$field] = array_values(array_filter(array_map(
                static fn (mixed $item): string => trim((string) $item),
                is_array($value) ? $value : [],
            ), static fn (string $item): bool => $item !== ''));
        }

        foreach (['itinerary', 'images'] as $field) {
            $value = $request->input($field, []);
            if (is_string($value)) {
                try {
                    $value = $value === '' ? [] : json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    $value = null;
                }
            }

            $normalized[$field] = is_array($value) ? $value : null;
        }

        $request->merge($normalized);
    }

    private function formData(Trip $trip): array
    {
        return [
            'trip' => $trip,
            'categories' => TripCategory::query()->orderBy('name')->get(['id', 'name']),
            'operators' => User::query()
                ->where('role', Role::Operator)
                ->where('account_status', AccountStatus::Active)
                ->where('approval_status', AccountApprovalStatus::Approved)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'vehicles' => Vehicle::query()
                ->where('status', '!=', ListingStatus::Archived)
                ->with('owner:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'owner_id', 'status']),
            'listingStatuses' => ListingStatus::cases(),
            'verificationStatuses' => VerificationStatus::cases(),
        ];
    }

    private function uniqueSlug(string $value, ?Trip $trip = null): string
    {
        $base = Str::slug($value) ?: 'trip';
        $slug = $base;
        $suffix = 2;

        while (Trip::query()->where('slug', $slug)->when($trip, fn (Builder $query): Builder => $query->whereKeyNot($trip->getKey()))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function syncImages(Trip $trip, array $images): void
    {
        $trip->images()->delete();

        foreach ($images as $sortOrder => $image) {
            $trip->images()->create([
                'url' => $image['url'],
                'alt' => $image['alt'] ?? $trip->name,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function writeAuditLog(Request $request, Trip $trip, string $action, array $fields, int $imageCount): void
    {
        $request->user()->adminLogs()->create([
            'action' => $action,
            'entity' => 'Trip',
            'entity_id' => $trip->id,
            'metadata' => ['fields' => $fields, 'image_count' => $imageCount],
        ]);
    }
}
