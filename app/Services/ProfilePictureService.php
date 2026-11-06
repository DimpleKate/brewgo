<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfilePictureService
{
    public const DISK = 'profile_pictures';

    public const DIRECTORY = 'images/profile-pictures';

    public function store(UploadedFile $file): string
    {
        $filename = $file->hashName();

        Storage::disk(self::DISK)->putFileAs('', $file, $filename);

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
