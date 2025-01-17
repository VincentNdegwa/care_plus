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
        try {
            $nowTime = Carbon::now();
            $ourLateTime = $nowTime->copy()->subHour();


            $schedules = MedicationSchedule::where('dose_time', '>=', $ourLateTime)
                ->where('dose_time', '<=', $nowTime)
                ->whereNull("processed_at")
                ->get();


            foreach ($schedules as $schedule) {
                Log::info("Processing schedule ID: " . $schedule->id);

                $schedule->processed_at = $nowTime;
                $schedule->save();
            }
        } catch (\Throwable $th) {
            Log::error("Error: " . $th->getMessage());
        }
    }
}
