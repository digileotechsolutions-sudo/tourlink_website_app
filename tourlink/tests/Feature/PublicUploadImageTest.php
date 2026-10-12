<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicUploadImageTest extends TestCase
{
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
}
