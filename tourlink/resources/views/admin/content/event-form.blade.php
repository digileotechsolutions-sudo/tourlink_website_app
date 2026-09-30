@extends('layouts.admin')

@php($editing = $event->exists)

@section('title', ($editing ? 'Edit event' : 'Create event').' | TourLink Admin')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8"><header class="border-b border-slate-200 pb-6"><a href="{{ route('admin.events.index') }}" class="text-sm font-semibold text-emerald-800">Back to events</a><h1 class="mt-3 text-3xl font-black">{{ $editing ? 'Edit event' : 'Create event' }}</h1></header>
        @if($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="POST" action="{{ $editing ? route('admin.events.update', $event) : route('admin.events.store') }}" class="mt-7 grid gap-5 sm:grid-cols-2">@csrf @if($editing)@method('PUT')@endif
            <label class="grid gap-1 text-sm font-semibold">Title<input name="title" value="{{ old('title', $event->title) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
            <label class="grid gap-1 text-sm font-semibold">Slug<input name="slug" value="{{ old('slug', $event->slug) }}" maxlength="255" placeholder="Generated when blank" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
            <label class="grid gap-1 text-sm font-semibold sm:col-span-2">Description<textarea name="description" required rows="5" class="rounded border border-slate-300 px-3 py-2 font-normal">{{ old('description', $event->description) }}</textarea></label>
            <label class="grid gap-1 text-sm font-semibold">Image URL<input type="url" name="image_url" value="{{ old('image_url', $event->image_url) }}" required class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
            <label class="grid gap-1 text-sm font-semibold">Location<input name="location" value="{{ old('location', $event->location) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
            <label class="grid gap-1 text-sm font-semibold">Start time<input type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
            <label class="flex items-center gap-2 self-end pb-3 text-sm font-semibold"><input type="checkbox" name="published" value="1" @checked(old('published', $event->published ?? false)) class="size-4 accent-emerald-800">Publish event</label>
            <footer class="flex justify-end gap-3 border-t border-slate-200 pt-5 sm:col-span-2"><a href="{{ route('admin.events.index') }}" class="rounded border border-slate-300 px-4 py-2.5 text-sm font-bold">Cancel</a><button class="rounded bg-emerald-800 px-5 py-2.5 text-sm font-bold text-white">{{ $editing ? 'Save event' : 'Create event' }}</button></footer>
        </form>
    </div>
@endsection