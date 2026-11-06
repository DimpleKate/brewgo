<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

abstract class RecordActivity
{
    /**
     * @return array<string, string>
     */
    abstract protected function actions(): array;

    abstract protected function guestRole(): string;

    abstract protected function fallbackAction(?string $routeName): string;

    public function handle(Request $request, Closure $next): Response
    {
        $actorBeforeRequest = $request->user();
        $response = $next($request);

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $response;
        }

        $actor = $request->user() ?? $actorBeforeRequest;
        $routeName = $request->route()?->getName();

        try {
            AuditLog::create([
                'user_id' => $actor?->id,
                'user_name' => $actor?->name ?? 'Guest',
                'actor_role' => $this->roleFor($actor),
                'action' => $this->actionFor($routeName),
                'route_name' => $routeName,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'context' => $this->contextFor($request),
            ]);
        } catch (\Throwable) {
        }

        return $response;
    }

    protected function roleFor(?User $actor): string
    {
        if (! $actor) {
            return $this->guestRole();
        }

        return $actor->isAdmin() ? 'admin' : 'customer';
    }

    private function actionFor(?string $routeName): string
    {
        $actions = $this->actions();

        if ($routeName !== null && isset($actions[$routeName])) {
            return $actions[$routeName];
        }

        return $this->fallbackAction($routeName);
    }

    private function contextFor(Request $request): array
    {
        $context = collect($request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'current_password',
        ]))
            ->reject(fn ($value, $key) => is_string($key) && str_ends_with($key, '_file'))
            ->take(20)
            ->map(function ($value) {
                if (is_array($value)) {
                    return array_slice($value, 0, 20);
                }

                return Str::limit((string) $value, 200);
            })
            ->all();

        $uploadedImages = collect($request->allFiles())
            ->flatten()
            ->map(fn ($file) => $file->getClientOriginalName())
            ->all();

        if ($uploadedImages !== []) {
            $context['uploaded_images'] = $uploadedImages;
        }

        return $context;
    }
}
