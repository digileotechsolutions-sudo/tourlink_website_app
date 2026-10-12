<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBlogImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_blog_post_with_an_uploaded_cover_image(): void
    {
        // UploadedFile::fake()->image() needs the GD extension, which some
        // environments do not provide.
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is required to fabricate test images.');
        }

        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)->post(route('admin.blog.store'), [
            'title' => 'A Journey Through the Highlands',
            'slug' => 'journey-through-the-highlands',
            'excerpt' => 'A short introduction to a highland escape.',
            'body' => 'A longer story about the journey.',
            'image' => UploadedFile::fake()->image('highlands.jpg'),
            'published' => '0',
        ])->assertRedirect();

        $post = BlogPost::query()->firstOrFail();

        $this->assertStringStartsWith('/media/blog/', $post->image_url);
        $this->assertCount(1, Storage::disk('public')->allFiles('blog-images'));

        $this->actingAs($admin)->get($post->image_url)->assertOk();
    }

    public function test_admin_cannot_create_a_blog_post_with_only_an_image_url(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)->post(route('admin.blog.store'), [
            'title' => 'A Journey Through the Highlands',
            'slug' => 'journey-through-the-highlands',
            'excerpt' => 'A short introduction to a highland escape.',
            'body' => 'A longer story about the journey.',
            'image_url' => 'https://example.com/highlands.jpg',
            'published' => '0',
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_admin_can_update_a_blog_post_without_replacing_its_cover_image(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $imageUrl = '/media/blog/existing-cover.jpg';
        $post = BlogPost::query()->create([
            'title' => 'A Journey Through the Highlands',
            'slug' => 'journey-through-the-highlands',
            'excerpt' => 'A short introduction to a highland escape.',
            'body' => 'A longer story about the journey.',
            'image_url' => $imageUrl,
            'published' => false,
        ]);

        $this->actingAs($admin)->put(route('admin.blog.update', $post), [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'body' => $post->body,
            'published' => '0',
        ])->assertRedirect();

        $this->assertSame($imageUrl, $post->fresh()->image_url);
    }
}
