<?php

namespace App\Service\Scheduler;

use App\Models\Schedules\MedicationLog;
use App\Models\Schedules\MedicationSchedule;
use App\Models\Schedules\MedicationTracker;

class ScheduleSaver
{

    public static function saveSchedule($medications_schedules, $medication_tracker)
    {
        foreach ($medications_schedules as $medication_schedule) {
            $mds = MedicationSchedule::create($medication_schedule);
        }
        $mdt = MedicationTracker::create($medication_tracker);
    }
}
