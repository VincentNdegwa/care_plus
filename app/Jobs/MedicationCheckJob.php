<?php

namespace App\Jobs;

use App\Events\MedicationTake;
use App\Models\Schedules\MedicationSchedule;
use App\Models\Schedules\MedicationScheduleNotification;
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
            $oneHourAgo = $nowTime->copy()->subHour();
            $twoHoursAgo = $nowTime->copy()->subHours(2);
            $threeHoursAgo = $nowTime->copy()->subHours(3);

            // Check for new medications that need to be processed
            $newSchedules = MedicationSchedule::where('dose_time', '>=', $oneHourAgo)
                ->where('dose_time', '<=', $nowTime)
                ->whereNull('processed_at')
                ->where('status', 'Pending')
                ->get();

            // Process new schedules
            foreach ($newSchedules as $schedule) {
                $schedule->processed_at = $nowTime;
                $schedule->save();

                MedicationTake::dispatch($schedule);
                SendMedicationDefaultNotification::dispatch($schedule);
                Log::info("Dispatched initial MedicationTake event for schedule ID: {$schedule->id}");
            }

            // Check for medications processed exactly 2 hours ago that are still pending
            // and haven't received second notification
            $pendingSchedules = MedicationSchedule::where('processed_at', '<=', $twoHoursAgo)
                ->where('processed_at', '>=', $threeHoursAgo->addMinutes(30))
                ->where('status', 'Pending')
                ->where('second_notification_sent', 0)  // Only get schedules that haven't received second notification
                ->get();

            // Send second notification for pending schedules
            foreach ($pendingSchedules as $schedule) {
                MedicationTake::dispatch($schedule);
                SendMedicationDefaultNotification::dispatch($schedule);
                
                // Mark that second notification has been sent
                $schedule->second_notification_sent = 1;
                $schedule->save();
                
                Log::info("Sent second notification for schedule ID: {$schedule->id} after 2 hours");
            }

            // Mark medications as missed if not taken after 3 hours
            $missedMedications = MedicationSchedule::where('dose_time', '<=', $threeHoursAgo)
                ->where('status', 'Pending')
                ->update(['status' => 'Missed']);

        } catch (\Throwable $th) {
            Log::error("Error: " . $th->getMessage());
        }
    }
}
