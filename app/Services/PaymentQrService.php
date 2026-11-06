<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PaymentQrService
{
    public const DISK = 'payment_qr';

    public const DIRECTORY = 'images/payment-qr';

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
