<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ContentImageService
{
    public const DISK = 'content_images';

    private const DIRECTORIES = ['site', 'categories', 'promotions', 'carousel'];

    public function store(UploadedFile $file, string $directory): string
    {
        if (! in_array($directory, self::DIRECTORIES, true)) {
            throw new \InvalidArgumentException('Unsupported content image directory.');
        }

        $filename = $file->hashName();

        Storage::disk(self::DISK)->putFileAs($directory, $file, $filename);

        return 'images/'.$directory.'/'.$filename;
    }

    public function delete(?string $path): void
    {
        if (! is_string($path) || ! str_starts_with($path, 'images/')) {
            return;
        }

        $relative = substr($path, strlen('images/'));
        $directory = str($relative)->before('/')->toString();

        if (! in_array($directory, self::DIRECTORIES, true)) {
            return;
        }

        Storage::disk(self::DISK)->delete($relative);
    }
}
