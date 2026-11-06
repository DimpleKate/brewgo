<?php

/**
 * Vercel serverless function entry point for Laravel.
 */

$bundledDatabase = __DIR__ . '/../database/database.sqlite';
$runtimeDatabase = getenv('DB_DATABASE') ?: '/tmp/database.sqlite';

if (!file_exists($runtimeDatabase) && file_exists($bundledDatabase)) {
    @copy($bundledDatabase, $runtimeDatabase);
}

if (!file_exists($runtimeDatabase)) {
    @touch($runtimeDatabase);
    $base = escapeshellarg(__DIR__ . '/..');
    @exec("cd {$base} && php artisan migrate --force 2>&1");
    @exec("cd {$base} && php artisan db:seed --class=DemoCatalogSeeder --force 2>&1");
}

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

$publicFile = __DIR__ . '/../public' . $uri;

if ($uri !== '/' && is_file($publicFile)) {
    header('Content-Type: ' . get_mime_type($publicFile));
    readfile($publicFile);
    return;
}

require __DIR__ . '/../public/index.php';

function get_mime_type($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $mimes = array(
        'txt' => 'text/plain',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'ttf' => 'application/x-font-ttf',
        'woff' => 'application/x-woff',
        'woff2' => 'font/woff2',
        'otf' => 'font/otf',
        'map' => 'application/json',
    );

    if (isset($mimes[$extension])) {
        return $mimes[$extension];
    }
    return 'application/octet-stream';
}
