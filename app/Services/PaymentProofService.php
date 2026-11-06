<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofService
{
    public const DISK = 'payment_proofs';

    public function store(UploadedFile $file): string
    {
        $filename = $file->hashName();

        Storage::disk(self::DISK)->putFileAs('', $file, $filename);

        return $filename;
    }

    public function delete(?string $path): void
    {
        $path = $this->normalize($path);

        if ($path === null) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function exists(?string $path): bool
    {
        $path = $this->normalize($path);

        return $path !== null && Storage::disk(self::DISK)->exists($path);
    }

    public function response(?string $path, string $filename = 'payment-proof'): StreamedResponse
    {
        $path = $this->normalize($path);

        abort_if($path === null || ! Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->response($path, $filename, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    /**
     * Accept legacy public paths such as images/payment-proofs/file.png and
     * return the filename stored on the private disk.
     */
    public function normalize(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));

        if (Str::contains($path, '..')) {
            return null;
        }

        if (Str::startsWith($path, 'images/payment-proofs/')) {
            return Str::after($path, 'images/payment-proofs/');
        }

        if (Str::startsWith($path, 'payment-proofs/')) {
            return Str::after($path, 'payment-proofs/');
        }

        return ltrim($path, '/');
    }
}
