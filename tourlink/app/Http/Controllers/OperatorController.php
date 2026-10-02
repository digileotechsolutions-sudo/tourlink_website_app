<?php

namespace App\Http\Controllers;

use App\BookingStatus;
use App\ListingStatus;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Review;
use App\Models\Trip;
use App\Models\TripAvailability;
use App\Models\TripCategory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VerificationRequest;
use App\OperatorVerificationDocument;
use App\VerificationLevel;
use App\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;
use Throwable;

class OperatorController extends Controller
{
    private const LIST_FIELDS = ['pickup_points', 'meals', 'activities', 'included_items', 'excluded_items'];

    public function dashboard(Request $request): View
    {
        $operator = $request->user();
        $tripIds = $operator->trips()->pluck('id');
        $bookings = Booking::query()->whereIn('trip_id', $tripIds);
        $verificationRequest = $this->latestVerificationRequest($operator);
        $uploadedDocumentKeys = collect($verificationRequest->documents ?? [])->pluck('key')->all();
        $requiredDocumentKeys = OperatorVerificationDocument::requiredKeys();
        $completedRequirements = count(array_intersect($requiredDocumentKeys, $uploadedDocumentKeys));
        $totalRequirements = count($requiredDocumentKeys);
        if (mb_strlen((string) $operator->operatorProfile?->description) >= 20) {
            $completedRequirements++;
        }
        $totalRequirements++;
        $verificationCompletionPercentage = 50 + (int) round(($completedRequirements / $totalRequirements) * 50);

        return view('operator.dashboard', [
            'verificationCompletionPercentage' => $verificationCompletionPercentage,
            'tripCount' => $operator->trips()->count(),
            'publishedTripCount' => $operator->trips()->where('status', ListingStatus::Published)->count(),
            'bookingCount' => (clone $bookings)->count(),
            'pendingBookingCount' => (clone $bookings)->where('status', BookingStatus::Pending)->count(),
            'customerCount' => (clone $bookings)->distinct('traveler_id')->count('traveler_id'),
            'earnings' => (int) (clone $bookings)->whereIn('status', [BookingStatus::Paid, BookingStatus::InProgress, BookingStatus::Completed])->sum('total_amount'),
            'recentBookings' => (clone $bookings)->with(['traveler:id,name,email', 'trip:id,name'])->latest()->limit(8)->get(),
            'trips' => $operator->trips()->withCount('bookings')->latest()->limit(5)->get(),
        ]);
    }

    public function trips(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_column(ListingStatus::cases(), 'value'))],
        ]);

        $trips = $request->user()->trips()
            ->with(['destination:id,name', 'category:id,name'])
            ->withCount('bookings')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('name', 'like', '%'.$search.'%'))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('operator.trips.index', compact('trips', 'filters'));
    }

    public function createTrip(): View
    {
        return view('operator.trips.form', $this->tripFormData(new Trip));
    }

    public function storeTrip(Request $request): RedirectResponse
    {
        $data = $this->validatedTripData($request);
        $images = $data['images'] ?? [];
        unset($data['images']);
        $images = $this->uploadedImages($request, $images);
        $data['operator_id'] = $request->user()->id;
        $data['status'] = ListingStatus::Draft;
        $data['verification_status'] = VerificationStatus::Pending;
        $data['featured'] = false;
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name']);

        $trip = DB::transaction(function () use ($data, $images): Trip {
            $trip = Trip::query()->create($data);
            $this->syncImages($trip, $images);

            return $trip;
        });
        $this->syncAvailability($request, $trip);

        return redirect()->route('operator.trips.edit', $trip)->with('status', 'Trip created and submitted for verification.');
    }

    public function editTrip(Request $request, Trip $trip): View
    {
        $this->ensureTripOwner($request, $trip);
        $trip->load('images');

        return view('operator.trips.form', $this->tripFormData($trip));
    }

    public function updateTrip(Request $request, Trip $trip): RedirectResponse
    {
        $this->ensureTripOwner($request, $trip);
        $data = $this->validatedTripData($request, $trip);
        $images = $data['images'] ?? [];
        unset($data['images']);
        $images = $this->uploadedImages($request, $images);
        unset($data['status'], $data['verification_status'], $data['featured'], $data['operator_id']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], $trip);

        DB::transaction(function () use ($trip, $data, $images): void {
            $trip->fill($data)->save();
            $this->syncImages($trip, $images);
        });
        $this->syncAvailability($request, $trip);

        return redirect()->route('operator.trips.edit', $trip)->with('status', 'Trip updated. It remains subject to verification.');
    }

    public function deleteTrip(Request $request, Trip $trip): RedirectResponse
    {
        $this->ensureTripOwner($request, $trip);
        abort_if($trip->bookings()->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::Refunded])->exists(), 409, 'Trips with active bookings cannot be deleted.');
        $trip->update(['status' => ListingStatus::Archived]);

        return redirect()->route('operator.trips.index')->with('status', 'Trip unpublished and archived.');
    }

    public function toggleTrip(Request $request, Trip $trip): RedirectResponse
    {
        $this->ensureTripOwner($request, $trip);
        if ($trip->verification_status !== VerificationStatus::Approved) {
            return back()->withErrors(['status' => 'A trip must be approved before it can be published.']);
        }

        $trip->update(['status' => $trip->status === ListingStatus::Published ? ListingStatus::Draft : ListingStatus::Published]);

        return back()->with('status', $trip->status === ListingStatus::Published ? 'Trip published.' : 'Trip unpublished.');
    }

    public function updateAvailability(Request $request, Trip $trip): RedirectResponse
    {
        $this->ensureTripOwner($request, $trip);
        $data = $request->validate([
            'availability' => ['required', 'array', 'max:100'],
            'availability.*.date' => ['required', 'date'],
            'availability.*.seats' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        DB::transaction(function () use ($trip, $data): void {
            $trip->availabilities()->delete();
            foreach ($data['availability'] as $entry) {
                TripAvailability::query()->create(['trip_id' => $trip->id, 'date' => $entry['date'], 'seats' => $entry['seats']]);
            }
        });

        return back()->with('status', 'Trip availability updated.');
    }

    public function profile(Request $request): View
    {
        $operator = $request->user();

        return view('operator.profile', [
            'profile' => $operator->operatorProfile()->firstOrCreate([], ['company_name' => $operator->name, 'slug' => Str::slug($operator->name)]),
            'verificationRequest' => $this->latestVerificationRequest($operator),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('operator_profiles', 'slug')->ignore($request->user()->operatorProfile?->id)],
            'description' => ['nullable', 'string', 'max:10000'],
            'logo_url' => ['nullable', 'url:http,https', 'max:2048'],
            'website' => ['nullable', 'url:http,https', 'max:2048'],
            'years_active' => ['required', 'integer', 'min:0', 'max:200'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ]);
        unset($data['logo']);
        if ($request->hasFile('logo')) {
            $data['logo_url'] = Storage::disk('public')->url($request->file('logo')->store('operator-logos', 'public'));
        }
        $request->user()->operatorProfile()->updateOrCreate([], $data);

        return back()->with('status', 'Company profile updated.');
    }

    public function requestVerification(Request $request): RedirectResponse
    {
        $documentLabels = collect(OperatorVerificationDocument::cases())
            ->mapWithKeys(fn (OperatorVerificationDocument $document): array => [$document->value => $document->label()])
            ->all();
        $rules = [
            'notes' => ['nullable', 'string', 'max:2000'],
            'company_profile' => ['required', 'string', 'min:20', 'max:5000'],
            'documents' => ['nullable', 'array'],
        ];
        foreach (OperatorVerificationDocument::cases() as $document) {
            $rules['documents.'.$document->value] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
        }
        $data = $request->validate($rules);

        $verificationRequest = $this->latestVerificationRequest($request->user());
        $existing = collect($verificationRequest->documents ?? [])->keyBy('key')->all();
        $documents = [];
        $replacedPaths = [];
        $storedPaths = [];
        $missing = [];

        foreach ($documentLabels as $key => $label) {
            $previous = $existing[$key] ?? null;
            $file = $request->file('documents.'.$key);

            if ($file) {
                $path = $file->store('verification-documents/operator/'.$key, 'local');
                if (! is_string($path)) {
                    foreach ($storedPaths as $storedPath) {
                        Storage::disk('local')->delete($storedPath);
                    }

                    throw ValidationException::withMessages(['documents.'.$key => 'That document could not be stored. Please try again.']);
                }

                $storedPaths[] = $path;
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

                continue;
            }

            if (is_array($previous) && is_string($previous['path'] ?? null)) {
                $documents[] = $previous;
            } elseif (OperatorVerificationDocument::from($key)->required()) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            foreach ($storedPaths as $storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            $errors = [];
            foreach ($missing as $key) {
                $errors['documents.'.$key] = 'Upload the '.$documentLabels[$key].'.';
            }

            throw ValidationException::withMessages($errors);
        }

        try {
            DB::transaction(function () use ($request, $data, $documents): void {
                $operator = $request->user();
                $profile = $operator->operatorProfile()->firstOrCreate([], [
                    'company_name' => $operator->operatorProfile?->company_name ?? $operator->name,
                    'slug' => Str::slug($operator->name).'-'.Str::lower(Str::random(8)),
                ]);
                $profile->update(['description' => $data['company_profile']]);
                VerificationRequest::query()->updateOrCreate(
                    ['user_id' => $operator->id, 'type' => 'OPERATOR'],
                    ['status' => VerificationStatus::Pending, 'notes' => $data['notes'] ?? null, 'documents' => $documents],
                );
                $operator->forceFill(['verification_level' => VerificationLevel::Basic])->save();
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            Log::error('Operator verification submission failed.', ['exception' => $exception::class]);

            return back()->withInput()->withErrors(['verification' => 'Your verification request could not be saved. Please try again.']);
        }

        foreach ($replacedPaths as $replacedPath) {
            Storage::disk('local')->delete($replacedPath);
        }

        return back()->with('status', 'Your documents were submitted for admin review.');
    }

    public function bookings(Request $request): View
    {
        $bookings = Booking::query()->whereIn('trip_id', $request->user()->trips()->select('id'))
            ->with(['traveler:id,name,email,phone', 'trip:id,name', 'payments'])
            ->latest()
            ->paginate(20);

        return view('operator.section', ['title' => 'Bookings', 'description' => 'Review traveler requests and update trip booking progress.', 'items' => $bookings, 'type' => 'bookings']);
    }

    public function updateBooking(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->trip && $booking->trip->operator_id === $request->user()->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(array_column(BookingStatus::cases(), 'value'))]]);
        $allowedTransitions = [
            BookingStatus::Pending->value => [BookingStatus::Confirmed->value, BookingStatus::Cancelled->value],
            BookingStatus::Confirmed->value => [BookingStatus::InProgress->value, BookingStatus::Cancelled->value],
            BookingStatus::Paid->value => [BookingStatus::InProgress->value, BookingStatus::Refunded->value],
            BookingStatus::InProgress->value => [BookingStatus::Completed->value, BookingStatus::Refunded->value],
            BookingStatus::Completed->value => [],
            BookingStatus::Cancelled->value => [],
            BookingStatus::Refunded->value => [],
        ];
        if ($data['status'] !== $booking->status->value && ! in_array($data['status'], $allowedTransitions[$booking->status->value], true)) {
            throw ValidationException::withMessages(['status' => 'That booking status transition is not allowed.']);
        }
        $booking->update(['status' => $data['status']]);

        return back()->with('status', 'Booking status updated.');
    }

    public function section(Request $request, string $section): View
    {
        $operator = $request->user();
        $tripIds = $operator->trips()->select('id');
        $data = match ($section) {
            'customers' => ['items' => User::query()->whereIn('id', Booking::query()->whereIn('trip_id', $tripIds)->select('traveler_id'))->withCount(['bookings as trip_bookings' => fn (Builder $query): Builder => $query->whereIn('trip_id', $tripIds)])->paginate(20)],
            'vehicles' => ['items' => Vehicle::query()->whereIn('id', $operator->trips()->whereNotNull('required_vehicle_id')->select('required_vehicle_id'))->with('owner:id,name')->paginate(20)],
            'reviews' => ['items' => Review::query()->whereIn('trip_id', $tripIds)->with(['author:id,name', 'trip:id,name'])->latest()->paginate(20)],
            'payments' => ['items' => Booking::query()->whereIn('trip_id', $tripIds)->with('payments')->latest()->paginate(20)],
            'earnings' => ['items' => $operator->trips()->withSum(['bookings as gross_earnings' => fn (Builder $query): Builder => $query->whereIn('status', [BookingStatus::Paid, BookingStatus::InProgress, BookingStatus::Completed])], 'total_amount')->paginate(20)],
            'analytics' => ['items' => $operator->trips()->withCount('bookings')->withSum('bookings', 'total_amount')->paginate(20)],
            'messages' => ['items' => $operator->trips()->whereHas('bookings.conversations')->with(['bookings.conversations.messages.sender'])->paginate(20)],
            'verification' => ['items' => VerificationRequest::query()->where('user_id', $operator->id)->latest()->paginate(20)],
            default => ['items' => collect()],
        };

        return view('operator.section', array_merge(['title' => Str::headline($section), 'description' => 'Operator workspace for '.Str::lower(Str::headline($section)).'.', 'type' => $section], $data));
    }

    private function latestVerificationRequest(User $operator): VerificationRequest
    {
        return VerificationRequest::query()
            ->where('user_id', $operator->id)
            ->where('type', 'OPERATOR')
            ->latest()
            ->first() ?? new VerificationRequest(['type' => 'OPERATOR']);
    }

    private function validatedTripData(Request $request, ?Trip $trip = null): array
    {
        $this->normalizeFields($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'description' => ['required', 'string', 'max:20000'], 'starting_point' => ['required', 'string', 'max:255'], 'ending_point' => ['required', 'string', 'max:255'],
            'destination_id' => ['required', 'string', 'exists:destinations,id'], 'category_id' => ['required', 'string', 'exists:trip_categories,id'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'], 'departure_date' => ['required', 'date'], 'return_date' => ['required', 'date', 'after:departure_date'],
            'price_per_person' => ['required', 'integer', 'min:0', 'max:100000000'], 'max_travelers' => ['required', 'integer', 'min:1', 'max:10000'],
            'min_travelers' => ['required', 'integer', 'min:1', 'lte:max_travelers'], 'available_seats' => ['required', 'integer', 'min:0', 'lte:max_travelers'],
            'pickup_points' => ['required', 'array', 'min:1'], 'pickup_points.*' => ['required', 'string', 'max:255'],
            'itinerary' => ['required', 'array', 'min:1'], 'itinerary.*.day' => ['required', 'integer', 'min:1'], 'itinerary.*.title' => ['required', 'string', 'max:120'], 'itinerary.*.detail' => ['required', 'string', 'max:4000'],
            'accommodation' => ['nullable', 'string', 'max:5000'], 'meals' => ['nullable', 'array'], 'meals.*' => ['string', 'max:255'], 'transport' => ['nullable', 'string', 'max:5000'],
            'activities' => ['nullable', 'array'], 'activities.*' => ['string', 'max:255'], 'included_items' => ['nullable', 'array'], 'included_items.*' => ['string', 'max:255'], 'excluded_items' => ['nullable', 'array'], 'excluded_items.*' => ['string', 'max:255'],
            'cancellation_policy' => ['required', 'string', 'max:10000'], 'required_vehicle_id' => ['nullable', 'string', 'exists:vehicles,id'],
            'images' => ['nullable', 'array', 'max:12'], 'images.*.url' => ['required', 'url:http,https', 'max:2048'], 'images.*.alt' => ['nullable', 'string', 'max:255'],
            'images_files' => ['nullable', 'array', 'max:12'], 'images_files.*' => ['image', 'max:5120'],
        ]);

        $reservedSeats = $trip?->bookings()->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::Refunded, BookingStatus::Completed])->sum('travelers') ?? 0;
        if ($data['max_travelers'] < $data['available_seats'] + $reservedSeats) {
            throw ValidationException::withMessages(['available_seats' => 'Available seats plus active bookings cannot exceed maximum travelers.']);
        }

        return $data;
    }

    private function normalizeFields(Request $request): void
    {
        $normalized = [];
        foreach (self::LIST_FIELDS as $field) {
            $value = $request->input($field, []);
            $normalized[$field] = is_string($value) ? array_values(array_filter(array_map('trim', preg_split('/\R/u', $value) ?: []))) : (is_array($value) ? $value : []);
        }
        $value = $request->input('itinerary', []);
        if (is_string($value)) {
            try {
                $value = $value === '' ? [] : json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $value = null;
            }
        }
        $normalized['itinerary'] = is_array($value) ? $value : null;
        $images = $request->input('images', []);
        if (! is_array($images) || $images === []) {
            $images = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $request->input('image_urls', '')) ?: [])));
            $images = array_map(static fn (string $url): array => ['url' => $url, 'alt' => null], $images);
        }
        $normalized['images'] = $images;
        $request->merge($normalized);
    }

    private function tripFormData(Trip $trip): array
    {
        return ['trip' => $trip, 'destinations' => Destination::query()->orderBy('name')->get(['id', 'name']), 'categories' => TripCategory::query()->orderBy('name')->get(['id', 'name']), 'vehicles' => Vehicle::query()->where('owner_id', auth()->id())->where('status', '!=', ListingStatus::Archived)->get(['id', 'name'])];
    }

    private function syncImages(Trip $trip, array $images): void
    {
        $trip->images()->delete();
        foreach ($images as $sortOrder => $image) {
            $trip->images()->create(['url' => $image['url'], 'alt' => $image['alt'] ?? $trip->name, 'sort_order' => $sortOrder]);
        }
    }

    /**
     * @param  array<int, array{url: string, alt?: ?string}>  $images
     * @return array<int, array{url: string, alt?: ?string}>
     */
    private function uploadedImages(Request $request, array $images): array
    {
        foreach ($request->file('images_files', []) as $file) {
            $images[] = ['url' => Storage::disk('public')->url($file->store('trip-images', 'public')), 'alt' => null];
        }

        return array_slice($images, 0, 12);
    }

    private function syncAvailability(Request $request, Trip $trip): void
    {
        $availability = $request->input('availability', []);
        if (! is_array($availability)) {
            return;
        }

        $validated = validator(['availability' => $availability], [
            'availability' => ['nullable', 'array', 'max:100'],
            'availability.*.date' => ['nullable', 'date'],
            'availability.*.seats' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ])->validate();
        $entries = array_values(array_filter($validated['availability'] ?? [], static fn (array $entry): bool => filled($entry['date'] ?? null)));
        $trip->availabilities()->delete();
        foreach ($entries as $entry) {
            $trip->availabilities()->create(['date' => $entry['date'], 'seats' => $entry['seats'] ?? 0]);
        }
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

    private function ensureTripOwner(Request $request, Trip $trip): void
    {
        abort_unless($trip->operator_id === $request->user()->id, 404);
    }
}
