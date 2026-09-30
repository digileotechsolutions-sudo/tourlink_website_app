<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlogController extends Controller
{
    public function image(Request $request, string $filename): StreamedResponse
    {
        abort_unless(preg_match('/\A[a-z0-9][a-z0-9._-]*\.(?:jpe?g|png|webp|gif)\z/i', $filename) === 1, 404);

        $imageUrl = route('blog.image', ['filename' => $filename], false);
        $post = BlogPost::query()->where('image_url', $imageUrl)->first();

        abort_unless($post && ($post->published || $request->user()?->role === Role::Admin), 404);

        $path = 'blog-images/'.$filename;
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

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
