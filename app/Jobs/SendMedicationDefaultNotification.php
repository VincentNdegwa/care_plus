<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Models\Schedules\MedicationSchedule;
use App\Models\Schedules\MedicationScheduleNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\FCM\FCMService;

class SendMedicationDefaultNotification implements ShouldQueue
{
    use Queueable;

    public $schedule;
    public $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($schedule)
    {
        $this->schedule = $schedule;
        $this->userId = Patient::find($schedule->patient_id)->user_id; 
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Create notification record
        $notification = MedicationScheduleNotification::updateOrCreate(
            [
                'medication_schedule_id' => $this->schedule->id,
                'status' => 'Pending'
            ],
            [
                'message' => "It's time to take your medication"
            ]
        );

        // Send FCM notification
        $fcm = new FCMService();
        $fcm->sendToUser(
            $this->userId,
            'Medication Reminder',
            "It's time to take your medication",
            [
                'type' => 'medication_reminder',
                'payload'=> $this->schedule->toArray(),
            ]
        );
    }
}
