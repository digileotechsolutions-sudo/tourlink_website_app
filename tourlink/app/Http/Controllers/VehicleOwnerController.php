<?php

namespace App\Http\Controllers;

use App\BookingStatus;
use App\ListingStatus;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAvailability;
use App\Models\VerificationRequest;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class VehicleOwnerController extends Controller
{
    public function dashboard(Request $request): View
    {
        $owner = $request->user();
        $bookings = Booking::query()->whereIn('vehicle_id', $owner->vehicles()->select('id'));
        $identityVerificationRequest = $this->latestVerificationRequest($owner, 'VEHICLE_OWNER_IDENTITY');
        $uploadedIdentityDocumentKeys = collect($identityVerificationRequest->documents ?? [])->pluck('key')->all();
        $uploadedRequiredDocumentCount = (int) in_array('owner_id', $uploadedIdentityDocumentKeys, true);
        $totalRequiredDocumentCount = 1;
        $requiredVehicleDocumentKeys = ['logbook', 'insurance', 'front_photo', 'rear_photo', 'left_photo', 'right_photo', 'interior_photo', 'number_plate_photo'];

        foreach ($owner->vehicles()->get() as $vehicle) {
            $verificationRequest = $this->latestVerificationRequest($owner, 'VEHICLE', $vehicle->id);
            $uploadedVehicleDocumentKeys = collect($verificationRequest->documents ?? [])->pluck('key')->all();
            $uploadedRequiredDocumentCount += count(array_intersect($requiredVehicleDocumentKeys, $uploadedVehicleDocumentKeys));
            $totalRequiredDocumentCount += count($requiredVehicleDocumentKeys);
        }
        $verificationCompletionPercentage = 50 + (int) round(($uploadedRequiredDocumentCount / $totalRequiredDocumentCount) * 50);

        return view('vehicle-owner.dashboard', [
            'verificationCompletionPercentage' => $verificationCompletionPercentage,
            'vehicleCount' => $owner->vehicles()->count(),
            'publishedVehicleCount' => $owner->vehicles()->where('status', ListingStatus::Published)->count(),
            'bookingCount' => (clone $bookings)->count(),
            'pendingBookingCount' => (clone $bookings)->where('status', BookingStatus::Pending)->count(),
            'earnings' => (int) (clone $bookings)->whereIn('status', [BookingStatus::Paid, BookingStatus::InProgress, BookingStatus::Completed])->sum('total_amount'),
            'rating' => Review::query()->whereIn('vehicle_id', $owner->vehicles()->select('id'))->avg('rating'),
            'recentBookings' => (clone $bookings)->with(['traveler:id,name,email', 'vehicle:id,name'])->latest()->limit(8)->get(),
        ]);
    }

    public function vehicles(Request $request): View
    {
        $vehicles = $request->user()->vehicles()->withCount('bookings')->latest()->paginate(12);

        return view('vehicle-owner.vehicles.index', compact('vehicles'));
    }

    public function createVehicle(): View
    {
        return view('vehicle-owner.vehicles.form', $this->formData(new Vehicle));
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $images = $this->uploadedImages($request, $data['images'] ?? []);
        unset($data['images']);
        $data['owner_id'] = $request->user()->id;
        $data['status'] = ListingStatus::Draft;
        $data['verification_status'] = VerificationStatus::Pending;
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name']);

        $vehicle = DB::transaction(function () use ($data, $images): Vehicle {
            $vehicle = Vehicle::query()->create($data);
            $this->syncImages($vehicle, $images);

            return $vehicle;
        });
        $this->syncAvailability($request, $vehicle);

        return redirect()->route('vehicle-owner.vehicles.edit', $vehicle)->with('status', 'Vehicle created and submitted for verification.');
    }

    public function editVehicle(Request $request, Vehicle $vehicle): View
    {
        $this->ensureOwner($request, $vehicle);
        $vehicle->load('images', 'availabilities');

        return view('vehicle-owner.vehicles.form', $this->formData($vehicle));
    }

    public function updateVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->ensureOwner($request, $vehicle);
        $data = $this->validatedData($request, $vehicle);
        $images = $this->uploadedImages($request, $data['images'] ?? []);
        unset($data['images'], $data['status'], $data['verification_status'], $data['owner_id']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], $vehicle);
        $data['status'] = ListingStatus::Draft;
        $data['verification_status'] = VerificationStatus::Pending;

        DB::transaction(function () use ($vehicle, $data, $images): void {
            $vehicle->fill($data)->save();
            $this->syncImages($vehicle, $images);
        });
        $this->syncAvailability($request, $vehicle);

        return back()->with('status', 'Vehicle updated and resubmitted for verification.');
    }

    public function deleteVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->ensureOwner($request, $vehicle);
        abort_if($vehicle->bookings()->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::Refunded])->exists(), 409, 'Vehicles with active bookings cannot be deleted.');
        $vehicle->update(['status' => ListingStatus::Archived]);

        return redirect()->route('vehicle-owner.vehicles.index')->with('status', 'Vehicle archived.');
    }

    public function toggleVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->ensureOwner($request, $vehicle);
        if ($vehicle->verification_status !== VerificationStatus::Approved) {
            return back()->withErrors(['status' => 'A vehicle must be approved before it can be published.']);
        }
        $vehicle->update(['status' => $vehicle->status === ListingStatus::Published ? ListingStatus::Draft : ListingStatus::Published]);

        return back()->with('status', $vehicle->status === ListingStatus::Published ? 'Vehicle published.' : 'Vehicle unpublished.');
    }

    public function profile(Request $request): View
    {
        $owner = $request->user();
        $vehicles = $owner->vehicles()->orderBy('name')->get();
        $vehicleVerificationRequests = VerificationRequest::query()
            ->where('user_id', $owner->id)
            ->where('type', 'VEHICLE')
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
            ->latest()
            ->get()
            ->keyBy('vehicle_id');

        return view('vehicle-owner.profile', [
            'profile' => $owner->vehicleOwnerProfile()->firstOrCreate([], ['business_name' => $owner->name]),
            'identityVerificationRequest' => $this->latestVerificationRequest($owner, 'VEHICLE_OWNER_IDENTITY'),
            'vehicles' => $vehicles,
            'vehicleVerificationRequests' => $vehicleVerificationRequests,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate(['business_name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000']]);
        $request->user()->vehicleOwnerProfile()->updateOrCreate([], $data);

        return back()->with('status', 'Vehicle-owner profile updated.');
    }

    public function requestIdentityVerification(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array'],
            'documents.owner_id' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents.driving_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $owner = $request->user();
        $verificationRequest = $this->latestVerificationRequest($owner, 'VEHICLE_OWNER_IDENTITY');
        $stored = $this->storePrivateVerificationDocuments(
            $request,
            $verificationRequest->documents ?? [],
            ['owner_id' => 'Owner ID or passport', 'driving_license' => 'Driving licence'],
            ['owner_id'],
            'vehicle-owner/identity',
        );

        try {
            VerificationRequest::query()->updateOrCreate(
                ['user_id' => $owner->id, 'type' => 'VEHICLE_OWNER_IDENTITY', 'vehicle_id' => null],
                ['status' => VerificationStatus::Pending, 'notes' => $data['notes'] ?? null, 'documents' => $stored['documents']],
            );
        } catch (Throwable $exception) {
            $this->deleteVerificationFiles($stored['new_paths']);
            throw $exception;
        }

        $this->deleteVerificationFiles($stored['replaced_paths']);
        $owner->forceFill(['verification_level' => \App\VerificationLevel::Basic])->save();

        return back()->with('status', 'Owner identity documents submitted for admin review.');
    }

    public function requestVehicleVerification(Request $request): RedirectResponse
    {
        $documentLabels = [
            'logbook' => 'Vehicle logbook',
            'insurance' => 'Vehicle insurance certificate',
            'inspection' => 'Motor vehicle inspection certificate',
            'front_photo' => 'Front vehicle photo',
            'rear_photo' => 'Rear vehicle photo',
            'left_photo' => 'Left side vehicle photo',
            'right_photo' => 'Right side vehicle photo',
            'interior_photo' => 'Vehicle interior photo',
            'number_plate_photo' => 'Number plate photo',
        ];
        $rules = [
            'vehicle_id' => ['required', 'string', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array'],
        ];
        foreach ($documentLabels as $key => $_label) {
            $rules['documents.'.$key] = str_ends_with($key, '_photo')
                ? ['nullable', 'image', 'max:5120']
                : ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
        }
        $data = $request->validate($rules);
        $owner = $request->user();
        $vehicle = $owner->vehicles()->whereKey($data['vehicle_id'])->firstOrFail();
        $verificationRequest = $this->latestVerificationRequest($owner, 'VEHICLE', $vehicle->id);
        $required = ['logbook', 'insurance', 'front_photo', 'rear_photo', 'left_photo', 'right_photo', 'interior_photo', 'number_plate_photo'];
        $stored = $this->storePrivateVerificationDocuments(
            $request,
            $verificationRequest->documents ?? [],
            $documentLabels,
            $required,
            'vehicle-owner/vehicles/'.$vehicle->id,
        );

        try {
            VerificationRequest::query()->updateOrCreate(
                ['user_id' => $owner->id, 'type' => 'VEHICLE', 'vehicle_id' => $vehicle->id],
                ['status' => VerificationStatus::Pending, 'notes' => $data['notes'] ?? null, 'documents' => $stored['documents']],
            );
            $vehicle->forceFill(['verification_status' => VerificationStatus::Pending, 'status' => ListingStatus::Draft])->save();
        } catch (Throwable $exception) {
            $this->deleteVerificationFiles($stored['new_paths']);
            throw $exception;
        }

        $this->deleteVerificationFiles($stored['replaced_paths']);

        return back()->with('status', 'Vehicle documents submitted for admin review.');
    }

    public function bookings(Request $request): View
    {
        $bookings = Booking::query()->whereIn('vehicle_id', $request->user()->vehicles()->select('id'))->with(['traveler:id,name,email,phone', 'vehicle:id,name', 'payments'])->latest()->paginate(20);

        return view('vehicle-owner.section', ['title' => 'Bookings', 'description' => 'Accept, reject, and manage vehicle booking requests.', 'items' => $bookings, 'type' => 'bookings']);
    }

    public function updateBooking(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->vehicle && $booking->vehicle->owner_id === $request->user()->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(array_column(BookingStatus::cases(), 'value'))]]);
        $allowed = [BookingStatus::Pending->value => [BookingStatus::Confirmed->value, BookingStatus::Cancelled->value], BookingStatus::Confirmed->value => [BookingStatus::InProgress->value, BookingStatus::Cancelled->value], BookingStatus::Paid->value => [BookingStatus::InProgress->value], BookingStatus::InProgress->value => [BookingStatus::Completed->value]];
        if ($data['status'] !== $booking->status->value && ! in_array($data['status'], $allowed[$booking->status->value] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'That booking status transition is not allowed.']);
        }
        $booking->update(['status' => $data['status']]);

        return back()->with('status', 'Booking status updated.');
    }

    public function section(Request $request, string $section): View
    {
        $owner = $request->user();
        $vehicleIds = $owner->vehicles()->select('id');
        $data = match ($section) {
            'earnings' => ['items' => Booking::query()->whereIn('vehicle_id', $vehicleIds)->whereIn('status', [BookingStatus::Paid, BookingStatus::InProgress, BookingStatus::Completed])->with('vehicle:id,name')->latest()->paginate(20)],
            'ratings' => ['items' => Review::query()->whereIn('vehicle_id', $vehicleIds)->with(['author:id,name', 'vehicle:id,name'])->latest()->paginate(20)],
            'messages' => ['items' => Booking::query()->whereIn('vehicle_id', $vehicleIds)->whereHas('conversations')->with(['conversations.messages.sender'])->latest()->paginate(20)],
            'verification' => ['items' => VerificationRequest::query()->where('user_id', $owner->id)->latest()->paginate(20)],
            default => ['items' => collect()],
        };

        return view('vehicle-owner.section', array_merge(['title' => Str::headline($section), 'description' => 'Vehicle-owner workspace for '.Str::lower(Str::headline($section)).'.', 'type' => $section], $data));
    }

    private function validatedData(Request $request, ?Vehicle $vehicle = null): array
    {
        $request->merge(['air_conditioning' => $request->boolean('air_conditioning'), 'four_by_four' => $request->boolean('four_by_four'), 'driver_included' => $request->boolean('driver_included')]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'], 'registration_number' => ['required', 'string', 'max:30'], 'make' => ['required', 'string', 'max:120'], 'model' => ['required', 'string', 'max:120'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 2)], 'body_type' => ['required', Rule::in(['Safari Land Cruiser', 'Safari Van', 'Toyota Hiace', 'SUV', 'Minibus', 'Bus', 'Sedan', '4x4', 'Other'])], 'engine_cc' => ['nullable', 'integer', 'min:0'], 'colour' => ['nullable', 'string', 'max:80'], 'seating_capacity' => ['required', 'integer', 'min:1', 'max:100'], 'tare_weight' => ['nullable', 'integer', 'min:0'], 'axles' => ['nullable', 'integer', 'min:1', 'max:10'], 'load_capacity' => ['nullable', 'integer', 'min:0'], 'transmission' => ['required', 'string', 'max:80'], 'fuel_type' => ['required', 'string', 'max:80'],
            'air_conditioning' => ['required', 'boolean'], 'four_by_four' => ['required', 'boolean'], 'driver_included' => ['required', 'boolean'], 'price_per_day' => ['required', 'integer', 'min:0', 'max:100000000'], 'location' => ['required', 'string', 'max:255'], 'destination_id' => ['nullable', 'string', 'exists:destinations,id'],
            'images' => ['nullable', 'array', 'max:12'], 'images.*.url' => ['required', 'url:http,https', 'max:2048'], 'images.*.alt' => ['nullable', 'string', 'max:255'], 'images_files' => ['nullable', 'array', 'max:12'], 'images_files.*' => ['image', 'max:5120'],
        ]);

        return $data;
    }

    private function formData(Vehicle $vehicle): array
    {
        return ['vehicle' => $vehicle, 'destinations' => Destination::query()->orderBy('name')->get(['id', 'name'])];
    }

    private function syncImages(Vehicle $vehicle, array $images): void
    {
        $vehicle->images()->delete();
        foreach ($images as $image) {
            $vehicle->images()->create(['url' => $image['url'], 'alt' => $image['alt'] ?? $vehicle->name]);
        }
    }

    private function uploadedImages(Request $request, array $images): array
    {
        foreach ($request->file('images_files', []) as $file) {
            $images[] = ['url' => Storage::disk('public')->url($file->store('vehicle-images', 'public')), 'alt' => null];
        }
        $urls = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $request->input('image_urls', '')) ?: [])));
        foreach ($urls as $url) {
            $images[] = ['url' => $url, 'alt' => null];
        }

        return array_slice($images, 0, 12);
    }

    private function latestVerificationRequest(User $user, string $type, ?string $vehicleId = null): VerificationRequest
    {
        $query = VerificationRequest::query()
            ->where('user_id', $user->id)
            ->where('type', $type);

        if ($vehicleId === null) {
            $query->whereNull('vehicle_id');
        } else {
            $query->where('vehicle_id', $vehicleId);
        }

        return $query->latest()->first() ?? new VerificationRequest(['type' => $type, 'vehicle_id' => $vehicleId]);
    }

    /**
     * Compliance documents remain private and are served through the guarded admin route.
     *
     * @param  array<int, array<string, mixed>>  $existing
     * @param  array<string, string>  $labels
     * @param  list<string>  $required
     * @return array{documents: array<int, array<string, mixed>>, new_paths: list<string>, replaced_paths: list<string>}
     */
    private function storePrivateVerificationDocuments(Request $request, array $existing, array $labels, array $required, string $directory): array
    {
        $documents = [];
        $newPaths = [];
        $replacedPaths = [];
        $missing = [];
        foreach ($labels as $key => $label) {
            $previous = collect($existing)->firstWhere('key', $key);
            $file = $request->file('documents.'.$key);

            if (! $file && is_array($previous) && is_string($previous['path'] ?? null)) {
                $documents[] = $previous;
                continue;
            }

            if (! $file) {
                if (in_array($key, $required, true)) {
                    $missing[] = $key;
                }
                continue;
            }

            $path = $file->store('verification-documents/'.$directory.'/'.$key, 'local');

            if (! is_string($path)) {
                $this->deleteVerificationFiles($newPaths);
                throw ValidationException::withMessages(['documents.'.$key => 'That file could not be stored. Please try again.']);
            }

            $newPaths[] = $path;
            if (is_array($previous) && is_string($previous['path'] ?? null)) {
                $replacedPaths[] = $previous['path'];
            }
            $documents[] = [
                'key' => $key,
                'label' => $label,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }

        if ($missing !== []) {
            $this->deleteVerificationFiles($newPaths);
            $errors = [];
            foreach ($missing as $key) {
                $errors['documents.'.$key] = 'Upload the '.$labels[$key].'.';
            }
            throw ValidationException::withMessages($errors);
        }

        return ['documents' => $documents, 'new_paths' => $newPaths, 'replaced_paths' => $replacedPaths];
    }

    /** @param list<string> $paths */
    private function deleteVerificationFiles(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('local')->delete($path);
        }
    }

    private function syncAvailability(Request $request, Vehicle $vehicle): void
    {
        $date = $request->input('availability.0.date');
        if (! $date) {
            return;
        }
        $data = $request->validate(['availability.0.date' => ['date'], 'availability.0.available' => ['nullable', 'boolean']]);
        VehicleAvailability::query()->updateOrCreate(['vehicle_id' => $vehicle->id, 'date' => $data['availability'][0]['date']], ['available' => $request->boolean('availability.0.available')]);
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

    private function ensureOwner(Request $request, Vehicle $vehicle): void
    {
        abort_unless($vehicle->owner_id === $request->user()->id, 404);
    }
}
