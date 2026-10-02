@extends('layouts.admin')

@section('title', 'Review moderation | Havenedge Tourlink Admin')

@section('content')
    <div class="mx-auto max-w-[1300px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="border-b border-slate-200 pb-6"><p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Trust and safety</p><h1 class="mt-2 text-3xl font-black text-slate-950">Reviews</h1><p class="mt-1 text-sm text-slate-600">Hide or restore reviews while preserving the original record.</p></header>
        @if(session('status'))<p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>@endif
        @if($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

        <form method="GET" class="mt-6 flex flex-wrap gap-3 border-b border-slate-200 pb-5">
            <label class="sr-only" for="review-search">Search reviews</label><input id="review-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Review, author, trip or vehicle" class="min-h-10 w-full max-w-md rounded border border-slate-300 px-3 text-sm">
            <label class="sr-only" for="review-visibility">Visibility</label><select id="review-visibility" name="visibility" class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm"><option value="">All reviews</option><option value="visible" @selected(($filters['visibility'] ?? '') === 'visible')>Visible</option><option value="hidden" @selected(($filters['visibility'] ?? '') === 'hidden')>Hidden</option></select>
            <button class="min-h-10 rounded border border-slate-300 px-4 text-sm font-bold">Filter</button><a href="{{ route('admin.reviews.index') }}" class="grid min-h-10 place-items-center px-2 text-sm text-slate-600">Clear</a>
        </form>

        @forelse($reviews as $review)
            <article class="grid gap-5 border-b border-slate-200 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(220px,0.6fr)]">
                <div>
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1"><h2 class="font-extrabold text-slate-950">{{ $review->author?->name ?? 'Unknown author' }}</h2><span class="text-sm font-bold text-orange-800">{{ $review->rating }} / 5</span><span class="text-xs text-slate-500">{{ $review->created_at?->format('M j, Y') }}</span></div>
                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $review->body }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ $review->trip?->name ?? $review->vehicle?->name ?? 'Listing unavailable' }} · Booking {{ $review->booking_id }}</p>
                    <span class="mt-2 inline-block text-[10px] font-bold uppercase {{ $review->hidden_by_admin ? 'text-red-700' : 'text-emerald-800' }}">{{ $review->hidden_by_admin ? 'Hidden from public' : 'Visible publicly' }}</span>
                </div>
                <form method="POST" action="{{ route('admin.reviews.visibility', $review) }}" class="grid content-start gap-3">
                    @csrf @method('PATCH')
                    <input type="hidden" name="hidden_by_admin" value="{{ $review->hidden_by_admin ? '0' : '1' }}">
                    <label class="grid gap-1 text-xs font-semibold text-slate-700">Moderation reason<textarea name="reason" required minlength="5" maxlength="1000" rows="2" class="rounded border border-slate-300 px-3 py-2 text-sm font-normal" placeholder="Record why visibility is changing."></textarea></label>
                    <button class="justify-self-start rounded border {{ $review->hidden_by_admin ? 'border-emerald-300 text-emerald-800' : 'border-red-300 text-red-700' }} px-4 py-2 text-sm font-bold">{{ $review->hidden_by_admin ? 'Restore review' : 'Hide review' }}</button>
                </form>
            </article>
        @empty<p class="py-10 text-sm text-slate-600">No reviews match these filters.</p>@endforelse
        <div class="mt-6">{{ $reviews->links() }}</div>
    </div>
@endsection