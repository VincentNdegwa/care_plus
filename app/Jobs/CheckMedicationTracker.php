<?php

namespace App\Jobs;

use App\Models\Schedules\MedicationTracker;
use App\Service\Scheduler\ScheduleExtender;
use App\Service\Scheduler\ScheduleSaver;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckMedicationTracker implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = Carbon::now();

        $medicationTrackers = MedicationTracker::whereBetween("next_start_month", [$now, $now->copy()->addWeek()])
            ->get();

        foreach ($medicationTrackers as $medicationTracker) {
            Log::info('Found active medication track ' . $medicationTracker->id);

            $scheduleData = ScheduleExtender::generateSchedule($medicationTracker);

            if (
                isset($scheduleData['medications_schedules']) && isset($scheduleData['medication_tracker'])
            ) {
                ScheduleSaver::saveSchedule(
                    $scheduleData['medications_schedules'],
                    $scheduleData['medication_tracker']
                );
            }
        }
    }
}
