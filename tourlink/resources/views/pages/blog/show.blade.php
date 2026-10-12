@extends('layouts.app')

@section('title', $blogPost->title.' | Havenedge Tourlink')
@section('content')
<article class="journal-article">
    <header class="journal-article__hero">
        <div class="container-page">
            <a class="journal-back-link" href="{{ route('blog.index') }}"><span aria-hidden="true">←</span>{{ __('Back to the journal') }}</a>
            <p class="journal-card__date">{{ $blogPost->created_at?->format('j M Y') }}</p>
            <h1>{{ $blogPost->title }}</h1>
            @if($blogPost->excerpt)<p class="journal-article__excerpt">{{ $blogPost->excerpt }}</p>@endif
        </div>
    </header>
    @if($blogPost->image_url)<div class="container-page"><img src="{{ $blogPost->image_url }}" alt="{{ $blogPost->title }}" class="journal-article__cover" fetchpriority="high" decoding="async"></div>@endif
    <div class="container-page journal-article__body">{!! nl2br(e($blogPost->body)) !!}</div>
</article>
@if($related->isNotEmpty())
<section class="journal-related"><div class="container-page"><h2>{{ __('Keep reading') }}</h2><div class="journal-grid">@foreach($related as $post)<article class="journal-card">@if($post->image_url)<a class="journal-card__image" href="{{ route('blog.show', ['blogPost' => $post->slug]) }}" aria-label="{{ $post->title }}"><img src="{{ $post->image_url }}" alt="" loading="lazy"></a>@endif<div class="journal-card__body"><p class="journal-card__date">{{ $post->created_at?->format('j M Y') }}</p><h3><a href="{{ route('blog.show', ['blogPost' => $post->slug]) }}">{{ $post->title }}</a></h3><p class="journal-card__excerpt">{{ $post->excerpt }}</p></div></article>@endforeach</div></div></section>
@endif
@endsection
