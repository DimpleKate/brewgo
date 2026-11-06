<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandAssetController extends Controller
{
    /**
     * Brand logo stored in the public images directory.
     */
    private const LOGO_PATH = 'images/brew-go-logo.png';

    public function favicon(): BinaryFileResponse
    {
        return $this->serveLogo();
    }

    public function logo(): BinaryFileResponse
    {
        return $this->serveLogo();
    }

    private function serveLogo(): BinaryFileResponse
    {
        $path = public_path(self::LOGO_PATH);

        abort_unless(is_file($path), 404);

        return response()
            ->file($path, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=86400',
            ]);
    }
}
