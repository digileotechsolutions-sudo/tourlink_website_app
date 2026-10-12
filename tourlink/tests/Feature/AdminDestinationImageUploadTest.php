<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminDestinationImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_destination_with_an_uploaded_image(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is required to fabricate test images.');
        }

        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)->post(route('admin.catalog.destinations.store'), [
            'name' => 'Maasai Mara',
            'slug' => 'maasai-mara',
            'country' => 'Kenya',
            'location' => 'Narok County',
            'description' => 'Wildlife and wide-open savannah.',
            'image' => UploadedFile::fake()->image('mara.jpg'),
            'attractions' => "Wildlife\nSavannah",
            'activities' => "Game drives\nGuided walks",
        ])->assertRedirect();

        $destination = Destination::query()->firstOrFail();
        $filePath = public_path('media/destination-images/'.basename($destination->image_url));

        try {
            $this->assertStringStartsWith('/media/destination-images/', $destination->image_url);
            $this->get($destination->image_url)->assertOk();
        } finally {
            @unlink($filePath);
            @rmdir(dirname($filePath));
        }
    }

    public function test_destination_creation_requires_an_uploaded_image_not_an_image_url(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)->post(route('admin.catalog.destinations.store'), [
            'name' => 'Maasai Mara',
            'slug' => 'maasai-mara',
            'country' => 'Kenya',
            'description' => 'Wildlife and wide-open savannah.',
            'image_url' => 'https://example.com/mara.jpg',
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('destinations', 0);
    }

    public function test_editing_a_destination_without_an_upload_keeps_its_current_image(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $imageUrl = '/media/destination-images/existing-mara.jpg';
        $destination = Destination::query()->create([
            'name' => 'Maasai Mara',
            'slug' => 'maasai-mara',
            'country' => 'Kenya',
            'description' => 'Wildlife and wide-open savannah.',
            'image_url' => $imageUrl,
        ]);

        $this->actingAs($admin)->put(route('admin.catalog.destinations.update', $destination), [
            'name' => $destination->name,
            'slug' => $destination->slug,
            'country' => $destination->country,
            'description' => $destination->description,
        ])->assertRedirect();

        $this->assertSame($imageUrl, $destination->fresh()->image_url);
    }
}