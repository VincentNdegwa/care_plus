<?php

namespace App\Jobs;

use App\Events\MedicationTake;
use App\Models\Schedules\MedicationSnooze;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSnoozeNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = Carbon::now();
        
        $snoozedMedications = MedicationSnooze::with('medicationSchedule')
            ->where('status', 'Pending')
            ->where('snooze_time', '<=', $now)
            ->get();

        foreach ($snoozedMedications as $snooze) {
            if ($snooze->medicationSchedule) {
                // Check if medication is already taken
                if ($snooze->medicationSchedule->status === 'Taken') {
                    $snooze->dismiss();
                    Log::info("Dismissed snooze for taken medication: {$snooze->medication_schedule_id}");
                    continue;
                }

                // Send notifications
                MedicationTake::dispatch($snooze->medicationSchedule);
                SendMedicationDefaultNotification::dispatch($snooze->medicationSchedule);
                
                // Mark as dismissed since we've sent the notification
                $snooze->dismiss();
                
                Log::info("Processed snooze notification for schedule: {$snooze->medication_schedule_id}");
            } else {
                // If schedule doesn't exist, dismiss the snooze
                $snooze->dismiss();
                Log::info("Dismissed orphaned snooze: {$snooze->id}");
            }
        }
    }
} 