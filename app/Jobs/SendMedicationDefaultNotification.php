<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Models\Schedules\MedicationSchedule;
use App\Models\Schedules\MedicationScheduleNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\FCM\FCMService;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Log;

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
        MedicationScheduleNotification::updateOrCreate(
            [
                'medication_schedule_id' => $this->schedule->id,
                'status' => 'Pending'
            ],
            [
                'message' => "It's time to take your medication"
            ]
        );

        $schedule = $this->schedule->load(['medication']);
        $scheduleArray = $schedule->toArray();
        
        $notification_service = new NotificationService();
        $notification_service->send(
            'medication_reminder',
            [$this->userId],
            [
                'Medication Name' => $scheduleArray['medication']['medication_name'] ?? '',
                'Dosage Quantity' => $scheduleArray['medication']['dosage_quantity'] ?? '',
                'Dosage Strength' => $scheduleArray['medication']['dosage_strength'] ?? ''
            ],
            [
                'type' => 'medication_reminder', 
                'payload' => $scheduleArray
            ]
        );
    }

}
