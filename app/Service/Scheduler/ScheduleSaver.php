<?php

namespace App\Service\Scheduler;

use App\Models\Schedules\MedicationSchedule;
use App\Models\Schedules\MedicationTracker;

class ScheduleSaver
{
    public static function saveSchedule($medications_schedules, $medication_tracker)
    {
        if (isset($medication_tracker['schedules']) && is_array($medication_tracker['schedules'])) {
            $medication_tracker['schedules'] = json_encode($medication_tracker['schedules']);
        }

        $mdt = MedicationTracker::updateOrCreate(
            [
                'medication_id' => $medication_tracker['medication_id'],
            ],
            $medication_tracker
        );

        foreach ($medications_schedules as $medication_schedule) {
            MedicationSchedule::firstOrCreate(
                [
                    'dose_time' => $medication_schedule['dose_time'],
                    'medication_id' => $medication_schedule['medication_id'],
                    'patient_id' => $medication_schedule['patient_id'],
                ],
                $medication_schedule
            );
        }
    }
}
