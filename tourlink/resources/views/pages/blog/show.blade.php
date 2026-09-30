@extends('layouts.app')

@section('title', $blogPost->title.' | TourLink')
@section('content')
<article class="container-page min-h-[60vh] py-10">
    <a class="text-xs font-bold text-lagoon" href="{{ route('blog.index') }}">&larr; Back to the journal</a>
    <header class="mt-4 max-w-3xl">
        <p class="text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">{{ $blogPost->created_at?->format('j M Y') }}</p>
        <h1 class="display mt-2 text-3xl font-bold text-ink sm:text-5xl">{{ $blogPost->title }}</h1>
        @if($blogPost->excerpt)<p class="mt-4 text-base leading-7 text-slate-600">{{ $blogPost->excerpt }}</p>@endif
    </header>
    @if($blogPost->image_url)<img src="{{ $blogPost->image_url }}" alt="{{ $blogPost->title }}" class="mt-8 max-h-[26rem] w-full rounded-2xl object-cover">@endif
    <div class="mt-8 max-w-3xl text-[15px] leading-7 text-slate-700">{!! nl2br(e($blogPost->body)) !!}</div>
</article>
@if($related->isNotEmpty())
<section class="border-t border-slate-200 bg-slate-50 py-10"><div class="container-page"><h2 class="font-extrabold text-ink">Keep reading</h2><div class="mt-5 grid gap-5 md:grid-cols-3">@foreach($related as $post)<article class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">@if($post->image_url)<img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="h-32 w-full object-cover" loading="lazy">@endif<div class="flex flex-1 flex-col p-4"><h3 class="text-sm font-extrabold text-ink"><a class="hover:text-lagoon" href="{{ route('blog.show', $post) }}">{{ $post->title }}</a></h3><p class="mt-2 line-clamp-3 text-xs leading-5 text-slate-600">{{ $post->excerpt }}</p></div></article>@endforeach</div></div></section>
@endif
@endsection
