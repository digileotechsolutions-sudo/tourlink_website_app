@extends('layouts.app')

@section('title', 'Travel journal | Havenedge Tourlink')
@section('content')
<section class="journal-hero">
    <div class="container-page">
        <p class="journal-eyebrow"><span aria-hidden="true"></span>{{ __('Travel journal') }}</p>
        <h1>{{ __('Stories for the road.') }}</h1>
        <p class="journal-hero__description">{{ __('Ideas, local knowledge and inspiration for wherever the road takes you.') }}</p>
        <form method="GET" action="{{ route('blog.index') }}" class="journal-search">
            <label class="sr-only" for="blog-search">{{ __('Search articles') }}</label>
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m16 16 4 4"></path></svg>
            <input id="blog-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search articles...') }}" type="search">
            <button type="submit">{{ __('Search') }}</button>
        </form>
    </div>
</section>
<section class="container-page journal-results" aria-labelledby="journal-results-heading">
    <div class="journal-results-heading">
        <div>
            <p>{{ __('From the journal') }}</p>
            <h2 id="journal-results-heading">{{ $posts->total() }} {{ Str::plural('article', $posts->total()) }}</h2>
        </div>
        @if(!empty($filters['search']))
            <a href="{{ route('blog.index') }}">{{ __('Clear search') }}<span aria-hidden="true">×</span></a>
        @endif
    </div>
    @if($posts->isEmpty())
        <div class="journal-empty-state">
            <span class="journal-empty-state__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4.5A2.5 2.5 0 0 1 7.5 2H20v18H7.5A2.5 2.5 0 0 0 5 22.5v-18Z"></path><path d="M5 18.5A2.5 2.5 0 0 1 7.5 16H20M9 7h7m-7 4h7"></path></svg></span>
            <h2>{{ __('No articles match that search') }}</h2>
            <p>{{ __('Try a different word, or browse everything we have published so far.') }}</p>
            <a href="{{ route('blog.index') }}">{{ __('Browse all articles') }}</a>
        </div>
    @else
        <div class="journal-grid">
            @foreach($posts as $post)
                <article class="journal-card">
                    <a class="journal-card__image" href="{{ route('blog.show', ['blogPost' => $post->slug]) }}" aria-label="{{ $post->title }}">
                        @if($post->image_url)
                            <img src="{{ $post->image_url }}" alt="" loading="lazy" decoding="async">
                        @else
                            <span class="journal-card__image-placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19 9 9l4 6 3-4 4 8H4Z"></path><circle cx="16.5" cy="6.5" r="2"></circle></svg></span>
                        @endif
                        <span class="journal-card__image-arrow" aria-hidden="true">↗</span>
                    </a>
                    <div class="journal-card__body">
                        <p class="journal-card__date">{{ $post->created_at?->format('j M Y') }}</p>
                        <h2><a href="{{ route('blog.show', ['blogPost' => $post->slug]) }}">{{ $post->title }}</a></h2>
                        <p class="journal-card__excerpt">{{ $post->excerpt }}</p>
                        <a class="journal-card__read" href="{{ route('blog.show', ['blogPost' => $post->slug]) }}">{{ __('Read article') }}<span aria-hidden="true">→</span></a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="journal-pagination">{{ $posts->links() }}</div>
    @endif
</section>
@endsection
