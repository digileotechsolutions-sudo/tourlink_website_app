<?php

namespace App\Http\Controllers;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\ListingStatus;
use App\Models\Destination;
use App\Models\User;
use App\Models\Vehicle;
use App\Role;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class AdminVehicleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_column(ListingStatus::cases(), 'value'))],
            'verification_status' => ['nullable', Rule::in(array_values(array_diff(array_column(VerificationStatus::cases(), 'value'), [VerificationStatus::UnderReview->value])))],
        ]);

        $vehicles = Vehicle::query()
            ->with(['owner:id,name,email', 'destination:id,name'])
            ->withCount('bookings')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('registration_number', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['verification_status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('verification_status', $status))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.vehicles.index', compact('vehicles', 'filters'));
    }

    public function create(): View
    {
        return view('admin.vehicles.form', $this->formData(new Vehicle));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $images = $data['images'] ?? [];
        unset($data['images'], $data['images_files']);
        $images = $this->uploadedImages($request, $images);
        $data['currency'] = 'KES';
        $data['submitted_at'] = ($data['verification_status'] ?? VerificationStatus::Pending->value) === VerificationStatus::Pending->value ? now() : null;
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? null) ?: $data['name']);

        $vehicle = DB::transaction(function () use ($request, $data, $images): Vehicle {
            $vehicle = Vehicle::query()->create($data);
            $this->syncImages($vehicle, $images);
            $this->audit($request, $vehicle, 'vehicle.created', array_keys($data), count($images));

            return $vehicle;
        });

        return redirect()->route('admin.vehicles.edit', $vehicle)->with('status', 'Vehicle created.');
    }

    public function edit(Vehicle $vehicle): View
    {
        $vehicle->load('images');

        return view('admin.vehicles.form', $this->formData($vehicle));
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $this->validatedData($request, $vehicle);
        $images = $data['images'] ?? [];
        unset($data['images'], $data['images_files']);
        $images = $this->uploadedImages($request, $images);
        $data['currency'] = 'KES';
        unset($data['submitted_at']);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? null) ?: $data['name'], $vehicle);

        DB::transaction(function () use ($request, $vehicle, $data, $images): void {
            $vehicle->fill($data)->save();
            $this->syncImages($vehicle, $images);
            $this->audit($request, $vehicle, 'vehicle.updated', array_keys($data), count($images));
        });

        return redirect()->route('admin.vehicles.edit', $vehicle)->with('status', 'Vehicle updated.');
    }

    public function archive(Request $request, Vehicle $vehicle): RedirectResponse
    {
        DB::transaction(function () use ($request, $vehicle): void {
            $vehicle->update(['status' => ListingStatus::Archived]);
            $this->audit($request, $vehicle, 'vehicle.archived', ['status'], $vehicle->images()->count());
        });

        return redirect()->route('admin.vehicles.index')->with('status', 'Vehicle archived. Booking history was preserved.');
    }

    private function validatedData(Request $request, ?Vehicle $vehicle = null): array
    {
        $images = $request->input('images', []);
        if (is_string($images)) {
            try {
                $images = $images === '' ? [] : json_decode($images, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $images = null;
            }
            $request->merge(['images' => $images]);
        }

        $request->merge([
            'air_conditioning' => $request->boolean('air_conditioning'),
            'four_by_four' => $request->boolean('four_by_four'),
            'driver_included' => $request->boolean('driver_included'),
        ]);
        $existingImageUrls = $vehicle?->images()->pluck('url')->all() ?? [];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('vehicles', 'slug')->ignore($vehicle?->id)],
            'registration_number' => ['required', 'string', 'max:30'],
            'make' => ['required', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:120'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 2)],
            'body_type' => ['required', 'string', 'max:120'],
            'engine_cc' => ['nullable', 'integer', 'min:0'],
            'colour' => ['nullable', 'string', 'max:80'],
            'seating_capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'tare_weight' => ['nullable', 'integer', 'min:0'],
            'axles' => ['nullable', 'integer', 'min:1', 'max:10'],
            'load_capacity' => ['nullable', 'integer', 'min:0'],
            'transmission' => ['required', 'string', 'max:80'],
            'fuel_type' => ['required', 'string', 'max:80'],
            'air_conditioning' => ['required', 'boolean'],
            'four_by_four' => ['required', 'boolean'],
            'driver_included' => ['required', 'boolean'],
            'price_per_day' => ['required', 'integer', 'min:0', 'max:100000000'],
            'hourly_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'trip_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'driver_charge' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'additional_charges' => ['nullable', 'array', 'max:20'],
            'additional_charges.*.name' => ['required', 'string', 'max:120'],
            'additional_charges.*.amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'service_areas' => ['nullable', 'array', 'max:50'],
            'service_areas.*' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'string', Rule::exists('users', 'id')->where('role', Role::VehicleOwner->value)->where('account_status', AccountStatus::Active->value)->where('approval_status', AccountApprovalStatus::Approved->value)],
            'destination_id' => ['nullable', 'string', 'exists:destinations,id'],
            'status' => ['required', Rule::in(array_column(ListingStatus::cases(), 'value'))],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*.url' => ['required', 'url:http,https', Rule::in($existingImageUrls), 'max:2048'],
            'images.*.alt' => ['nullable', 'string', 'max:255'],
            'images_files' => ['nullable', 'array', 'max:12'],
            'images_files.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'dimensions:min_width=320,min_height=240', 'max:'.config('services.tourlink.vehicle_image_max_kb', 5120)],
        ]);

        if (count($data['images'] ?? []) + count($request->file('images_files', [])) > 12) {
            throw ValidationException::withMessages(['images_files' => 'A vehicle can have up to 12 images.']);
        }

        $imageUploadBytes = array_sum(array_map(static fn (UploadedFile $file): int => $file->getSize(), $request->file('images_files', [])));
        if ($imageUploadBytes > config('services.tourlink.vehicle_image_total_max_kb', 6144) * 1024) {
            throw ValidationException::withMessages(['images_files' => 'Combined vehicle photos exceed the upload size limit. Compress or remove photos and try again.']);
        }

        $data['verification_status'] = $vehicle?->verification_status->value ?? VerificationStatus::Pending->value;

        if ($data['status'] === ListingStatus::Published->value && $data['verification_status'] !== VerificationStatus::Approved->value) {
            throw ValidationException::withMessages(['status' => 'A vehicle must be approved before it can be published.']);
        }

        return $data;
    }

    private function formData(Vehicle $vehicle): array
    {
        return [
            'vehicle' => $vehicle,
            'owners' => User::query()->where('role', Role::VehicleOwner)->where('account_status', AccountStatus::Active)->where('approval_status', AccountApprovalStatus::Approved)->orderBy('name')->get(['id', 'name', 'email']),
            'destinations' => Destination::query()->orderBy('name')->get(['id', 'name']),
            'listingStatuses' => ListingStatus::cases(),
        ];
    }

    private function uniqueSlug(string $value, ?Vehicle $vehicle = null): string
    {
        $base = Str::slug($value) ?: 'vehicle';
        $slug = $base;
        $suffix = 2;

        while (Vehicle::query()->where('slug', $slug)->when($vehicle, fn (Builder $query): Builder => $query->whereKeyNot($vehicle->getKey()))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function syncImages(Vehicle $vehicle, array $images): void
    {
        $vehicle->images()->delete();

        foreach ($images as $sortOrder => $image) {
            $vehicle->images()->create([
                'url' => $image['url'],
                'alt' => $image['alt'] ?? $vehicle->name,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    /**
     * @param  array<int, array{url: string, alt?: ?string}>  $images
     * @return array<int, array{url: string, alt?: ?string}>
     */
    private function uploadedImages(Request $request, array $images): array
    {
        foreach ($request->file('images_files', []) as $file) {
            $path = $file->store('vehicle-images', 'public');
            $images[] = ['url' => Storage::disk('public')->url($path), 'alt' => null];
        }

        return array_slice($images, 0, 12);
    }

    private function audit(Request $request, Vehicle $vehicle, string $action, array $fields, int $imageCount): void
    {
        $request->user()->adminLogs()->create([
            'action' => $action,
            'entity' => 'Vehicle',
            'entity_id' => $vehicle->id,
            'metadata' => ['fields' => $fields, 'image_count' => $imageCount],
        ]);
    }
}
