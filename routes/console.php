<?php

use App\Jobs\CheckSnoozeNotificationsJob;
use App\Jobs\MedicationCheckJob;
use App\Jobs\TestJobNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new MedicationCheckJob)->everyMinute();
Schedule::command('telescope:prune --hours=48')->daily();

Schedule::job(new CheckSnoozeNotificationsJob)->everyMinute();
// Schedule::job(new TestJobNotification)->everyMinute(); 