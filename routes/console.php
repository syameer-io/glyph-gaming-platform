<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// For testing/demo purposes - fetch Steam data every 5 minutes
Schedule::command('app:fetch-steam-data')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/steam-fetch.log'));

// Demo command to show scheduler is working
Schedule::call(function () {
    \Log::info('Scheduler is running at ' . now()->format('Y-m-d H:i:s'));
})->everyMinute();