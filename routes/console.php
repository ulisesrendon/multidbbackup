<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Run all due database backups at the top of every hour (minute 0).
 * The command itself decides which schedules are due based on
 * their configured frequency and last_backup_at timestamp.
 */
Schedule::command('backup:run-scheduled')
    ->hourlyAt(0)
    ->withoutOverlapping()
    ->runInBackground();

