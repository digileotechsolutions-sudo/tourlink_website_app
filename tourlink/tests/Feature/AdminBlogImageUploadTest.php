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

        $this->assertStringContainsString('/storage/blog-images/', $post->image_url);
        $this->assertCount(1, Storage::disk('public')->allFiles('blog-images'));
    }
}