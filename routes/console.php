<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:daily-reset')->dailyAt('00:05')->withoutOverlapping();
Schedule::command('queue:purge-old')->dailyAt('00:10')->withoutOverlapping();
Schedule::command('queue:process-timeouts')->everyMinute()->withoutOverlapping();
