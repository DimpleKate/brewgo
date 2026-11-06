<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class HealthController extends Controller
{
    public function index(): View
    {
        return view('admin.health.index', [
            'checks' => $this->checks(),
            'pendingJobs' => $this->pendingJobs(),
            'failedJobs' => $this->failedJobs(),
            'schedulerLastRun' => Cache::get('scheduler:last-run'),
            'queueConnection' => config('queue.default'),
            'mailer' => config('mail.default'),
            'environment' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'appUrl' => config('app.url'),
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
        ]);
    }

    public function retry(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! Schema::hasTable('failed_jobs')) {
            return back()->with('error', 'The failed_jobs table is not available.');
        }

        $id = $validated['id'] ?? null;

        Artisan::call('queue:retry', ['id' => $id ? [$id] : ['all']]);

        return back()->with('success', $id
            ? 'Failed job #'.$id.' was queued for retry.'
            : 'All failed jobs were queued for retry.');
    }

    public function flush(): RedirectResponse
    {
        if (! Schema::hasTable('failed_jobs')) {
            return back()->with('error', 'The failed_jobs table is not available.');
        }

        Artisan::call('queue:flush');

        return back()->with('success', 'All failed jobs were removed.');
    }

    /**
     * @return array<int, array{label:string, ok:bool, detail:string}>
     */
    private function checks(): array
    {
        $checks = [];

        try {
            DB::select('select 1');
            $checks[] = ['label' => 'Database', 'ok' => true, 'detail' => 'Connection succeeded.'];
        } catch (Throwable $exception) {
            report($exception);
            $checks[] = ['label' => 'Database', 'ok' => false, 'detail' => 'Connection failed.'];
        }

        $checks[] = [
            'label' => 'Storage',
            'ok' => is_writable(storage_path()),
            'detail' => is_writable(storage_path()) ? 'storage/ is writable.' : 'storage/ is not writable.',
        ];

        $proofDirectory = storage_path('app/private/payment-proofs');

        $checks[] = [
            'label' => 'Private payment proofs',
            'ok' => is_dir($proofDirectory) && is_writable($proofDirectory),
            'detail' => is_dir($proofDirectory)
                ? 'Private proof storage is available.'
                : 'Private proof storage folder has not been created yet.',
        ];

        try {
            $key = 'health-check:'.now()->timestamp;
            Cache::put($key, 'ok', 5);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);
            $checks[] = ['label' => 'Cache', 'ok' => $ok, 'detail' => $ok ? 'Cache reads and writes.' : 'Cache check failed.'];
        } catch (Throwable $exception) {
            report($exception);
            $checks[] = ['label' => 'Cache', 'ok' => false, 'detail' => 'Cache is unavailable.'];
        }

        $scheduler = Cache::get('scheduler:last-run');
        $schedulerOk = $scheduler && now()->diffInMinutes($scheduler) <= 5;

        $checks[] = [
            'label' => 'Scheduler',
            'ok' => (bool) $schedulerOk,
            'detail' => $scheduler
                ? 'Last heartbeat '.$scheduler->diffForHumans().'.'
                : 'No scheduler heartbeat recorded yet. Run php artisan schedule:work or configure cron.',
        ];

        $checks[] = [
            'label' => 'Debug mode',
            'ok' => ! config('app.debug'),
            'detail' => config('app.debug')
                ? 'APP_DEBUG is enabled. Disable it in production.'
                : 'APP_DEBUG is disabled.',
        ];

        return $checks;
    }

    private function pendingJobs(): int
    {
        return Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
    }

    /**
     * @return Collection<int, object>
     */
    private function failedJobs()
    {
        if (! Schema::hasTable('failed_jobs')) {
            return collect();
        }

        return DB::table('failed_jobs')
            ->latest('failed_at')
            ->limit(25)
            ->get()
            ->map(function (object $job) {
                $payload = json_decode((string) $job->payload, true);

                return (object) [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'name' => $payload['displayName'] ?? 'Unknown job',
                    'exception' => str($job->exception)->before("\n")->limit(200)->toString(),
                    'failed_at' => $job->failed_at,
                ];
            });
    }
}
