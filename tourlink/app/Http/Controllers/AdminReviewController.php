<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'visibility' => ['nullable', Rule::in(['visible', 'hidden'])],
        ]);

        $reviews = Review::query()
            ->with(['author:id,name,email', 'trip:id,name', 'vehicle:id,name'])
            ->when(isset($filters['visibility']), fn (Builder $query): Builder => $query->where('hidden_by_admin', $filters['visibility'] === 'hidden'))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('body', 'like', '%'.$search.'%')
                    ->orWhereHas('author', fn (Builder $author): Builder => $author->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                    ->orWhereHas('trip', fn (Builder $trip): Builder => $trip->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('vehicle', fn (Builder $vehicle): Builder => $vehicle->where('name', 'like', '%'.$search.'%'));
            }))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'filters'));
    }

    public function updateVisibility(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'hidden_by_admin' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $review->update(['hidden_by_admin' => $data['hidden_by_admin']]);
        $request->user()->adminLogs()->create([
            'action' => $data['hidden_by_admin'] ? 'review.hidden' : 'review.restored',
            'entity' => 'Review',
            'entity_id' => $review->id,
            'metadata' => ['reason' => $data['reason'], 'hidden_by_admin' => (bool) $data['hidden_by_admin']],
        ]);

        return back()->with('status', $data['hidden_by_admin'] ? 'Review hidden from public pages.' : 'Review restored to public pages.');
    }
}
