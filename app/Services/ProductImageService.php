<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductImageService
{
    public const DISK = 'product_images';

    public const DIRECTORY = 'images/products/uploads';

    public function store(UploadedFile $file): string
    {
        $filename = $file->hashName();

        $stored = Storage::disk(self::DISK)->putFileAs('', $file, $filename);

        if ($stored === false) {
            throw new RuntimeException('The product picture could not be saved. Please try again.');
        }

        return self::DIRECTORY.'/'.$filename;
    }

    public function delete(?string $path): void
    {
        $prefix = self::DIRECTORY.'/';

        if (! is_string($path) || ! str_starts_with($path, $prefix)) {
            return;
        }

        Storage::disk(self::DISK)->delete(substr($path, strlen($prefix)));
    }
}
