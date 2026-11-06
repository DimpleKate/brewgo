<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\PreventDuplicateProductSubmission;
use App\Http\Middleware\PreventSensitiveResponseCaching;
use App\Http\Middleware\ShareSiteContent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'admin.role' => EnsureAdminRole::class,
            'active' => EnsureAccountIsActive::class,
            'product.submit-once' => PreventDuplicateProductSubmission::class,
        ]);

        $middleware->web(append: [
            ShareSiteContent::class,
            PreventSensitiveResponseCaching::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
