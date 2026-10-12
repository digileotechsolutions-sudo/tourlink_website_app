<?php

namespace Tests\Feature;

use App\Services\PublicImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicUploadImageTest extends TestCase
{
    public function test_uploaded_jpeg_is_stored_with_a_matching_extension_and_public_url(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is required to fabricate test images.');
        }

        Storage::fake('public');

        $url = app(PublicImageStorage::class)->store(UploadedFile::fake()->image('safari.jpeg'), 'trip-images');
        $storedPath = substr($url, strlen('/storage/'));

        $this->assertStringStartsWith('/storage/trip-images/', $url);
        $this->assertStringEndsWith('.jpg', $url);
        Storage::disk('public')->assertExists($storedPath);
        $this->get($url)->assertOk();
    }

    public function test_uploaded_trip_image_is_served_from_the_public_disk_without_a_storage_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('trip-images/namanga-cover.jpg', 'image-data');

        $this->get('/storage/trip-images/namanga-cover.jpg')
            ->assertOk()
            ->assertHeader('Cache-Control', 'public, max-age=604800');
    }

    public function test_private_verification_documents_are_not_exposed_by_the_public_upload_route(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('verification-documents/owner-id.jpg', 'private-data');

        $this->get('/storage/verification-documents/owner-id.jpg')->assertNotFound();
    }

    public function test_missing_public_uploads_fallback_to_a_placeholder_image(): void
    {
        Storage::fake('public');

        $this->get('/storage/trip-images/missing-trip-cover.jpg')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }
}
