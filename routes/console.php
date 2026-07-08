<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled tasks
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('queue:prune-batches --hours=48')->daily();

// Cleanup old activity logs (>90 days)
Schedule::command('model:prune --model=App\\Models\\ActivityLog')->daily();

// Cache cleanup
Schedule::command('cache:prune-stale-tags')->hourly();

// Daily database backup
Schedule::command('backup:database')->dailyAt('02:00');

// Smart behavioral notifications — runs every 4 hours
Schedule::command('notifications:dispatch')->everyFourHours();

// Daily analytics aggregation
Schedule::job(new \App\Jobs\DailyAnalyticsJob)->dailyAt('23:55');
