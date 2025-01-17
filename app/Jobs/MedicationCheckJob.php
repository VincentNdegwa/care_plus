<?php

namespace App\Jobs;

use App\Models\Schedules\MedicationSchedule;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MedicationCheckJob implements ShouldQueue
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
        $nowTime = Carbon::now();
        $ourLateTime = $nowTime->copy()->subHour();

        $schedules = MedicationSchedule::whereBetween('dose_time', [$ourLateTime, $nowTime])
            ->whereNull("processed_at")
            ->get();

        foreach ($schedules as $schedule) {
            $schedule->update(["processed_at" => $nowTime]);
            Log::info("Schedule " . $schedule);
        }
    }
}
