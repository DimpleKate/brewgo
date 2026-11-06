<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSensitiveResponseCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isProtectedArea = $request->user() !== null
            || $request->is('admin', 'admin/*')
            || $request->is('login', 'register', 'forgot-password', 'reset-password*');

        if (! $isProtectedArea) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
