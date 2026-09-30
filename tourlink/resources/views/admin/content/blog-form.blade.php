@extends('layouts.admin')

@php($editing = $post->exists)

@section('title', ($editing ? 'Edit post' : 'New post').' | TourLink Admin')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8"><header class="border-b border-slate-200 pb-6"><a href="{{ route('admin.blog.index') }}" class="text-sm font-semibold text-emerald-800">Back to blog</a><h1 class="mt-3 text-3xl font-black">{{ $editing ? 'Edit post' : 'New post' }}</h1></header>
        @if($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.blog.update', $post) : route('admin.blog.store') }}" class="mt-7 grid gap-5">@csrf @if($editing)@method('PUT')@endif
            <div class="grid gap-5 sm:grid-cols-2"><label class="grid gap-1 text-sm font-semibold">Title<input name="title" value="{{ old('title', $post->title) }}" required maxlength="255" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label><label class="grid gap-1 text-sm font-semibold">Slug<input name="slug" value="{{ old('slug', $post->slug) }}" maxlength="255" placeholder="Generated when blank" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label></div>
            <label class="grid gap-1 text-sm font-semibold">Excerpt<textarea name="excerpt" required maxlength="1000" rows="3" class="rounded border border-slate-300 px-3 py-2 font-normal">{{ old('excerpt', $post->excerpt) }}</textarea></label>
            <label class="grid gap-1 text-sm font-semibold">Article body<textarea name="body" required maxlength="100000" rows="16" class="rounded border border-slate-300 px-3 py-2 font-normal">{{ old('body', $post->body) }}</textarea></label>
            @if($editing && $post->image_url)<div class="grid gap-2 text-sm font-semibold"><span>Current image</span><img src="{{ $post->image_url }}" alt="Current cover for {{ $post->title }}" class="h-48 w-full max-w-md rounded-lg border border-slate-200 object-cover"></div>@endif
            <label class="grid gap-1 text-sm font-semibold">Upload cover image<input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" class="min-h-11 rounded border border-slate-300 bg-white px-3 py-2 font-normal"> <span class="text-xs font-normal text-slate-500">JPG, PNG, WebP, or GIF; maximum 5 MB.</span></label>
            <label class="grid gap-1 text-sm font-semibold">Or use an image URL<input type="url" name="image_url" value="{{ old('image_url', $post->image_url) }}" class="min-h-11 rounded border border-slate-300 px-3 font-normal"></label>
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="published" value="1" @checked(old('published', $post->published ?? false)) class="size-4 accent-emerald-800">Publish post</label>
            <footer class="flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('admin.blog.index') }}" class="rounded border border-slate-300 px-4 py-2.5 text-sm font-bold">Cancel</a><button class="rounded bg-emerald-800 px-5 py-2.5 text-sm font-bold text-white">{{ $editing ? 'Save post' : 'Create post' }}</button></footer>
        </form>
    </div>
@endsection