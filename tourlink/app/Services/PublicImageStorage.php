<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicImageStorage
{
    /**
     * @var array<string, string>
     */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/avif' => 'avif',
        'image/heic' => 'heic',
        'image/heic-sequence' => 'heic',
        'image/heif' => 'heif',
        'image/heif-sequence' => 'heif',
        'image/bmp' => 'bmp',
        'image/x-ms-bmp' => 'bmp',
        'image/tiff' => 'tiff',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];

    /**
     * Store the image with a normalized extension and return its same-site URL.
     */
    public function store(UploadedFile $file, string $directory, string $errorKey = 'images_files'): string
    {
        $extension = $this->extensionFor($file);

        if ($extension === null) {
            throw ValidationException::withMessages([
                $errorKey => 'The uploaded file type could not be identified as a supported image.',
            ]);
        }

        $path = $file->storeAs($directory, Str::ulid().'.'.$extension, 'public');
        $disk = Storage::disk('public');

        if (! is_string($path) || ! $disk->exists($path)) {
            if (is_string($path)) {
                $disk->delete($path);
            }

            throw ValidationException::withMessages([
                $errorKey => 'The image could not be saved. Check the server storage permissions and retry the upload.',
            ]);
        }

        return '/storage/'.$path;
    }

    private function extensionFor(UploadedFile $file): ?string
    {
        $mimeType = strtolower((string) $file->getMimeType());
        if (isset(self::MIME_EXTENSIONS[$mimeType])) {
            return self::MIME_EXTENSIONS[$mimeType];
        }

        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'heic', 'heif', 'bmp', 'tif', 'tiff', 'ico'], true)
            ? $extension
            : null;
    }
}
