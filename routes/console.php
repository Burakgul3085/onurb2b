<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('ops:backup')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('ops:cleanup')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('ops:notify-dues')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('ops:critical-stock')->dailyAt('08:05')->withoutOverlapping();
Schedule::command('ops:daily-report')->dailyAt('08:10')->withoutOverlapping();
Schedule::command('ops:import-mail')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
