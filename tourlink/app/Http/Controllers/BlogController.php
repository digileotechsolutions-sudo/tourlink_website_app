<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $posts = BlogPost::query()
            ->where('published', true)
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('excerpt', 'like', '%'.$search.'%'))
            ->latest('created_at')
            ->paginate(9)
            ->withQueryString();

        return view('pages.blog.index', compact('posts', 'filters'));
    }

    public function show(BlogPost $blogPost): View
    {
        abort_unless($blogPost->published, 404);

        $related = BlogPost::query()
            ->where('published', true)
            ->whereKeyNot($blogPost->getKey())
            ->latest('created_at')
            ->limit(3)
            ->get(['title', 'slug', 'excerpt', 'image_url', 'created_at']);

        return view('pages.blog.show', compact('blogPost', 'related'));
    }
}
