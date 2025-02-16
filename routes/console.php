<?php

use App\Jobs\CheckSnoozeNotificationsJob;
use App\Jobs\DeactivateMedication;
use App\Jobs\MedicationCheckJob;
use Illuminate\Support\Facades\Schedule;


Schedule::job(new MedicationCheckJob)->everyMinute();

Schedule::job(new CheckSnoozeNotificationsJob)->everyMinute();
Schedule::job(new DeactivateMedication)->daily();
// Schedule::job(new TestJobNotification)->everyMinute(); 