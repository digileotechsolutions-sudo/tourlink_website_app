@extends('layouts.admin')

@section('title', 'Destinations and categories | TourLink')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Administration</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950">Destinations and categories</h1>
                <p class="mt-2 text-sm text-slate-600">Maintain the catalog used by trip and vehicle listings.</p>
            </div>
            <a href="{{ route('admin.trips.index') }}" class="rounded border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-800 hover:bg-slate-50">Manage trips</a>
        </header>

        @if (session('status'))
            <p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950">
                <p class="font-bold">The catalog change could not be saved.</p>
                @foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach
            </div>
        @endif

        <section class="mt-8 grid gap-8 lg:grid-cols-[2fr_1fr]" aria-label="Create catalog entries">
            <form method="POST" action="{{ route('admin.catalog.destinations.store') }}" class="grid gap-4 border-b border-slate-200 pb-8 sm:grid-cols-2">
                @csrf
                <h2 class="col-span-full text-xl font-extrabold text-slate-950">Add destination</h2>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Name<input name="name" value="{{ old('name') }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Slug <span class="font-normal text-slate-500">Generated when blank</span><input name="slug" value="{{ old('slug') }}" maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Country<input name="country" value="{{ old('country', 'Kenya') }}" required maxlength="120" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Location<input name="location" value="{{ old('location') }}" maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700 sm:col-span-2">Image URL<input type="url" name="image_url" value="{{ old('image_url') }}" required placeholder="https://" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700 sm:col-span-2">Description<textarea name="description" required rows="3" class="rounded border border-slate-300 px-3 py-2 font-normal">{{ old('description') }}</textarea></label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Attractions <span class="font-normal text-slate-500">One per line</span><textarea name="attractions" rows="4" class="rounded border border-slate-300 px-3 py-2 font-normal">{{ old('attractions') }}</textarea></label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Activities <span class="font-normal text-slate-500">One per line</span><textarea name="activities" rows="4" class="rounded border border-slate-300 px-3 py-2 font-normal">{{ old('activities') }}</textarea></label>
                <div class="sm:col-span-2"><button class="rounded bg-emerald-800 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-900">Add destination</button></div>
            </form>

            <form method="POST" action="{{ route('admin.catalog.categories.store') }}" class="grid content-start gap-4 border-b border-slate-200 pb-8">
                @csrf
                <h2 class="text-xl font-extrabold text-slate-950">Add trip category</h2>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Name<input name="name" value="{{ old('name') }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Slug <span class="font-normal text-slate-500">Generated when blank</span><input name="slug" value="{{ old('slug') }}" maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
                <button class="justify-self-start rounded bg-emerald-800 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-900">Add category</button>
            </form>
        </section>

        <section class="mt-9" aria-labelledby="destinations-heading">
            <div class="flex items-baseline justify-between border-b border-slate-200 pb-3">
                <h2 id="destinations-heading" class="text-xl font-extrabold text-slate-950">Destinations</h2>
                <span class="text-sm text-slate-500">{{ $destinations->total() }} total</span>
            </div>
            @forelse ($destinations as $destination)
                <article class="border-b border-slate-200 py-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-4">
                            <img src="{{ $destination->image_url }}" alt="" loading="lazy" class="size-16 rounded object-cover">
                            <div><h3 class="font-bold text-slate-950">{{ $destination->name }}</h3><p class="mt-1 text-sm text-slate-600">{{ $destination->country }} · {{ $destination->location ?? 'No location' }} · {{ $destination->trips_count }} trips · {{ $destination->vehicles_count }} vehicles</p><p class="mt-1 text-xs text-slate-500">{{ $destination->slug }}</p></div>
                        </div>
                        <div class="flex items-center gap-4">
                            <details><summary class="cursor-pointer text-sm font-bold text-emerald-800 hover:underline">Edit</summary>
                                <form method="POST" action="{{ route('admin.catalog.destinations.update', $destination) }}" class="mt-4 grid w-[min(700px,calc(100vw-3rem))] gap-3 border-t border-slate-200 pt-4 sm:grid-cols-2">
                                    @csrf @method('PUT')
                                    <label class="grid gap-1 text-xs font-semibold">Name<input name="name" value="{{ $destination->name }}" required maxlength="255" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-semibold">Slug<input name="slug" value="{{ $destination->slug }}" maxlength="255" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-semibold">Country<input name="country" value="{{ $destination->country }}" required maxlength="120" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-semibold">Location<input name="location" value="{{ $destination->location }}" maxlength="255" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-semibold sm:col-span-2">Image URL<input type="url" name="image_url" value="{{ $destination->image_url }}" required class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                    <label class="grid gap-1 text-xs font-semibold sm:col-span-2">Description<textarea name="description" required rows="3" class="rounded border border-slate-300 px-3 py-2 text-sm font-normal">{{ $destination->description }}</textarea></label>
                                    <label class="grid gap-1 text-xs font-semibold">Attractions <span class="font-normal text-slate-500">One per line</span><textarea name="attractions" rows="4" class="rounded border border-slate-300 px-3 py-2 text-sm font-normal">{{ implode("\n", $destination->attractions ?? []) }}</textarea></label>
                                    <label class="grid gap-1 text-xs font-semibold">Activities <span class="font-normal text-slate-500">One per line</span><textarea name="activities" rows="4" class="rounded border border-slate-300 px-3 py-2 text-sm font-normal">{{ implode("\n", $destination->activities ?? []) }}</textarea></label>
                                    <button class="justify-self-start rounded bg-emerald-800 px-4 py-2.5 text-sm font-bold text-white">Save destination</button>
                                </form>
                            </details>
                            @if ($destination->trips_count === 0 && $destination->vehicles_count === 0)
                                <form method="POST" action="{{ route('admin.catalog.destinations.delete', $destination) }}" onsubmit="return confirm('Delete this destination?')">@csrf @method('DELETE')<button class="text-sm font-bold text-red-700 hover:underline">Delete</button></form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <p class="py-6 text-sm text-slate-600">No destinations yet.</p>
            @endforelse
            <div class="mt-4">{{ $destinations->links() }}</div>
        </section>

        <section class="mt-10" aria-labelledby="categories-heading">
            <div class="flex items-baseline justify-between border-b border-slate-200 pb-3">
                <h2 id="categories-heading" class="text-xl font-extrabold text-slate-950">Trip categories</h2>
                <span class="text-sm text-slate-500">{{ $categories->total() }} total</span>
            </div>
            @forelse ($categories as $category)
                <article class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 py-4">
                    <div><h3 class="font-bold text-slate-950">{{ $category->name }}</h3><p class="mt-1 text-sm text-slate-500">{{ $category->slug }} · {{ $category->trips_count }} trips</p></div>
                    <div class="flex items-center gap-4">
                        <details><summary class="cursor-pointer text-sm font-bold text-emerald-800 hover:underline">Edit</summary>
                            <form method="POST" action="{{ route('admin.catalog.categories.update', $category) }}" class="mt-4 grid w-[min(500px,calc(100vw-3rem))] gap-3 border-t border-slate-200 pt-4 sm:grid-cols-2">
                                @csrf @method('PUT')
                                <label class="grid gap-1 text-xs font-semibold">Name<input name="name" value="{{ $category->name }}" required maxlength="255" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                <label class="grid gap-1 text-xs font-semibold">Slug<input name="slug" value="{{ $category->slug }}" maxlength="255" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                <button class="justify-self-start rounded bg-emerald-800 px-4 py-2.5 text-sm font-bold text-white">Save category</button>
                            </form>
                        </details>
                        @if ($category->trips_count === 0)
                            <form method="POST" action="{{ route('admin.catalog.categories.delete', $category) }}" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button class="text-sm font-bold text-red-700 hover:underline">Delete</button></form>
                        @endif
                    </div>
                </article>
            @empty
                <p class="py-6 text-sm text-slate-600">No categories yet.</p>
            @endforelse
            <div class="mt-4">{{ $categories->links() }}</div>
        </section>
    </div>
@endsection
