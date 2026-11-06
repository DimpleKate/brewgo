<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Cache::put('scheduler:last-run', now(), now()->addHour());
})->everyMinute()->name('scheduler-heartbeat');

Schedule::command('orders:start-preparation')->everyMinute()->withoutOverlapping();
Schedule::command('orders:flag-overdue')->everyMinute()->withoutOverlapping();
Schedule::command('orders:expire-unpaid')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('audit:prune')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->weekly()->withoutOverlapping();
