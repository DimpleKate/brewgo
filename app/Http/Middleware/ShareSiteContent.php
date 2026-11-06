<?php

namespace App\Http\Middleware;

use App\Services\SiteContentService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareSiteContent
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share('site', app(SiteContentService::class)->all());

        return $next($request);
    }
}
