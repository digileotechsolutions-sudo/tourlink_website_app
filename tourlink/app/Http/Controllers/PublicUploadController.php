<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicUploadController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        abort_unless(preg_match('~^(?:trip-images|vehicle-images|blog-images|operator-logos|traveler-avatars)/[A-Za-z0-9_-][A-Za-z0-9._-]*$~D', $path) === 1, 404);

        $mediaPath = public_path('media/'.$path);
        if (! is_file($mediaPath)) {
            $disk = Storage::disk('public');
            $mediaPath = $disk->exists($path)
                ? $disk->path($path)
                : public_path('form-image1.png');
            abort_unless(is_file($mediaPath), 404);
        }

        $extensionMimeType = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'avif' => 'image/avif',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            'bmp' => 'image/bmp',
            'tif', 'tiff' => 'image/tiff',
            'ico' => 'image/x-icon',
            default => abort(404),
        };
        $contentType = (new \finfo(FILEINFO_MIME_TYPE))->file($mediaPath);

        return response()->file($mediaPath, [
            'Content-Type' => is_string($contentType) && str_starts_with($contentType, 'image/') ? $contentType : $extensionMimeType,
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
