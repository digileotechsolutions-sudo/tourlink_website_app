<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\TripCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCatalogController extends Controller
{
    public function index(): View
    {
        return view('admin.catalog.index', [
            'destinations' => Destination::query()->withCount(['trips', 'vehicles'])->orderBy('name')->paginate(20, ['*'], 'destinations_page'),
            'categories' => TripCategory::query()->withCount('trips')->orderBy('name')->paginate(20, ['*'], 'categories_page'),
        ]);
    }

    public function storeDestination(Request $request): RedirectResponse
    {
        $data = $this->destinationData($request);
        $data['slug'] = $this->uniqueSlug(Destination::class, $data['slug'] ?: $data['name']);

        DB::transaction(function () use ($request, $data): void {
            $destination = Destination::query()->create($data);
            $this->audit($request, 'destination.created', 'Destination', $destination->id, array_keys($data));
        });

        return back()->with('status', 'Destination created.');
    }

    public function updateDestination(Request $request, Destination $destination): RedirectResponse
    {
        $data = $this->destinationData($request, $destination);
        $data['slug'] = $this->uniqueSlug(Destination::class, $data['slug'] ?: $data['name'], $destination->id);

        DB::transaction(function () use ($request, $destination, $data): void {
            $destination->update($data);
            $this->audit($request, 'destination.updated', 'Destination', $destination->id, array_keys($data));
        });

        return back()->with('status', 'Destination updated.');
    }

    public function deleteDestination(Request $request, Destination $destination): RedirectResponse
    {
        if ($destination->trips()->exists() || $destination->vehicles()->exists()) {
            return back()->withErrors(['destination' => 'A destination used by trips or vehicles cannot be deleted.']);
        }

        DB::transaction(function () use ($request, $destination): void {
            $id = $destination->id;
            $destination->delete();
            $this->audit($request, 'destination.deleted', 'Destination', $id, []);
        });

        return back()->with('status', 'Destination deleted.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $this->categoryData($request);
        $data['slug'] = $this->uniqueSlug(TripCategory::class, $data['slug'] ?: $data['name']);

        DB::transaction(function () use ($request, $data): void {
            $category = TripCategory::query()->create($data);
            $this->audit($request, 'category.created', 'TripCategory', $category->id, array_keys($data));
        });

        return back()->with('status', 'Category created.');
    }

    public function updateCategory(Request $request, TripCategory $category): RedirectResponse
    {
        $data = $this->categoryData($request, $category);
        $data['slug'] = $this->uniqueSlug(TripCategory::class, $data['slug'] ?: $data['name'], $category->id);

        DB::transaction(function () use ($request, $category, $data): void {
            $category->update($data);
            $this->audit($request, 'category.updated', 'TripCategory', $category->id, array_keys($data));
        });

        return back()->with('status', 'Category updated.');
    }

    public function deleteCategory(Request $request, TripCategory $category): RedirectResponse
    {
        if ($category->trips()->exists()) {
            return back()->withErrors(['category' => 'A category assigned to trips cannot be deleted.']);
        }

        DB::transaction(function () use ($request, $category): void {
            $id = $category->id;
            $category->delete();
            $this->audit($request, 'category.deleted', 'TripCategory', $id, []);
        });

        return back()->with('status', 'Category deleted.');
    }

    private function destinationData(Request $request, ?Destination $destination = null): array
    {
        $request->merge([
            'attractions' => $this->lines($request->input('attractions')),
            'activities' => $this->lines($request->input('activities')),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('destinations', 'slug')->ignore($destination?->id)],
            'country' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:10000'],
            'image_url' => ['required', 'url:http,https', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'attractions' => ['nullable', 'array', 'max:40'],
            'attractions.*' => ['required', 'string', 'max:255'],
            'activities' => ['nullable', 'array', 'max:40'],
            'activities.*' => ['required', 'string', 'max:255'],
        ]);
    }

    private function categoryData(Request $request, ?TripCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('trip_categories', 'slug')->ignore($category?->id)],
        ]);

        return $data;
    }

    private function lines(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\R/u', $value) ?: [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            is_array($value) ? $value : [],
        ), static fn (string $item): bool => $item !== ''));
    }

    private function uniqueSlug(string $model, string $value, ?string $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($model::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function audit(Request $request, string $action, string $entity, string $entityId, array $fields): void
    {
        $request->user()->adminLogs()->create([
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'metadata' => ['fields' => $fields],
        ]);
    }
}
