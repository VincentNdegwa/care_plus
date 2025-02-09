<?php

namespace App\Jobs;

use App\Events\EventTest;
use App\Models\Patient;
use App\Services\FCM\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TestJobNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public $schedule;
    public $userId;

    public function __construct()
    {
        $schedule = [
            "id" => 26,
            "medication_id" => 1,
            "patient_id" => 1,
            "dose_time" => "2025-02-08 15:11:21",
            "processed_at" => "2025-02-08 15:12:04",
            "status" => "Pending",
            "taken_at" => null,
            "second_notification_sent" => 1,
            "created_at" => "2025-02-07T18:11:21.000000Z",
            "updated_at" => "2025-02-08T17:12:06.000000Z",
            "medication" => [
                "id" => 1,
                "patient_id" => 1,
                "diagnosis_id" => null,
                "medication_name" => "Panadols",
                "dosage_quantity" => "2",
                "dosage_strength" => "500mg",
                "form_id" => 3,
                "route_id" => 2,
                "frequency" => "Three times a day",
                "duration" => "3 days",
                "prescribed_date" => "2025-02-07 17:33:25",
                "doctor_id" => null,
                "caregiver_id" => null,
                "stock" => 20,
                "active" => 1
            ]
        ];

        $this->schedule = $schedule;
        $this->userId = Patient::find($schedule['patient_id'])->user_id; 

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
       // Send FCM notification
       $fcm = new FCMService();
       $fcm->sendToUser(
           $this->userId,
           'Medication Reminder',
           "It's time to take your medication",
           [
               'type' => 'medication_reminder',
               'payload'=> $this->schedule,
           ]
       );
    }
}
