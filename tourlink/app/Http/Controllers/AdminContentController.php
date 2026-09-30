<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminContentController extends Controller
{
    public function events(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120'], 'published' => ['nullable', 'boolean']]);
        $events = Event::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(fn (Builder $query): Builder => $query->where('title', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%')))
            ->when(isset($filters['published']), fn (Builder $query): Builder => $query->where('published', (bool) $filters['published']))
            ->orderByDesc('starts_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.content.events', compact('events', 'filters'));
    }

    public function createEvent(): View
    {
        return view('admin.content.event-form', ['event' => new Event]);
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        $data = $this->eventData($request);
        $event = DB::transaction(function () use ($request, $data): Event {
            $event = Event::query()->create($data);
            $this->audit($request, 'event.created', 'Event', $event->id, array_keys($data));

            return $event;
        });

        return redirect()->route('admin.events.edit', $event)->with('status', 'Event created.');
    }

    public function editEvent(Event $event): View
    {
        return view('admin.content.event-form', compact('event'));
    }

    public function updateEvent(Request $request, Event $event): RedirectResponse
    {
        $data = $this->eventData($request, $event);
        DB::transaction(function () use ($request, $event, $data): void {
            $event->update($data);
            $this->audit($request, 'event.updated', 'Event', $event->id, array_keys($data));
        });

        return redirect()->route('admin.events.edit', $event)->with('status', 'Event updated.');
    }

    public function toggleEvent(Request $request, Event $event): RedirectResponse
    {
        $event->update(['published' => ! $event->published]);
        $this->audit($request, $event->published ? 'event.published' : 'event.unpublished', 'Event', $event->id, ['published']);

        return back()->with('status', $event->published ? 'Event published.' : 'Event unpublished.');
    }

    public function blog(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120'], 'published' => ['nullable', 'boolean']]);
        $posts = BlogPost::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(fn (Builder $query): Builder => $query->where('title', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%')))
            ->when(isset($filters['published']), fn (Builder $query): Builder => $query->where('published', (bool) $filters['published']))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.content.blog', compact('posts', 'filters'));
    }

    public function createPost(): View
    {
        return view('admin.content.blog-form', ['post' => new BlogPost]);
    }

    public function storePost(Request $request): RedirectResponse
    {
        $data = $this->postData($request);
        $post = DB::transaction(function () use ($request, $data): BlogPost {
            $post = BlogPost::query()->create($data);
            $this->audit($request, 'blog_post.created', 'BlogPost', $post->id, array_keys($data));

            return $post;
        });

        return redirect()->route('admin.blog.edit', $post)->with('status', 'Blog post created.');
    }

    public function editPost(BlogPost $blogPost): View
    {
        return view('admin.content.blog-form', ['post' => $blogPost]);
    }

    public function updatePost(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $data = $this->postData($request, $blogPost);
        DB::transaction(function () use ($request, $blogPost, $data): void {
            $blogPost->update($data);
            $this->audit($request, 'blog_post.updated', 'BlogPost', $blogPost->id, array_keys($data));
        });

        return redirect()->route('admin.blog.edit', $blogPost)->with('status', 'Blog post updated.');
    }

    public function togglePost(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $blogPost->update(['published' => ! $blogPost->published]);
        $this->audit($request, $blogPost->published ? 'blog_post.published' : 'blog_post.unpublished', 'BlogPost', $blogPost->id, ['published']);

        return back()->with('status', $blogPost->published ? 'Blog post published.' : 'Blog post unpublished.');
    }

    private function eventData(Request $request, ?Event $event = null): array
    {
        $request->merge(['published' => $request->boolean('published')]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('events', 'slug')->ignore($event?->id)],
            'description' => ['required', 'string', 'max:10000'],
            'image_url' => ['required', 'url:http,https', 'max:2048'],
            'starts_at' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'published' => ['required', 'boolean'],
        ]);
        $data['slug'] = $this->slug(Event::class, $data['slug'] ?: $data['title'], $event?->id);

        return $data;
    }

    private function postData(Request $request, ?BlogPost $post = null): array
    {
        $request->merge(['published' => $request->boolean('published')]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('blog_posts', 'slug')->ignore($post?->id)],
            'excerpt' => ['required', 'string', 'max:1000'],
            'body' => ['required', 'string', 'max:100000'],
            'image_url' => ['required', 'url:http,https', 'max:2048'],
            'published' => ['required', 'boolean'],
        ]);
        $data['slug'] = $this->slug(BlogPost::class, $data['slug'] ?: $data['title'], $post?->id);

        return $data;
    }

    private function slug(string $model, string $value, ?string $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'content';
        $slug = $base;
        $suffix = 2;

        while ($model::query()->where('slug', $slug)->when($ignoreId, fn (Builder $query): Builder => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function audit(Request $request, string $action, string $entity, string $id, array $fields): void
    {
        $request->user()->adminLogs()->create(['action' => $action, 'entity' => $entity, 'entity_id' => $id, 'metadata' => ['fields' => $fields]]);
    }
}
