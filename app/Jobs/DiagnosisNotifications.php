<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DiagnosisNotifications implements ShouldQueue
{
    use Queueable;
    public $diagnosis;
    public $userId;


    /**
     * Create a new job instance.
     */
    public function __construct($diagnosis)
    {
        $this->diagnosis = $diagnosis;
        $this->userId = Patient::find($diagnosis->patient_id)->user_id; 
    }

    /**. 
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->diagnosis->toArray();
        $notification_service = new NotificationService();
        $notification_service->send(
            'new_diagnosis_notification',
            [$this->userId],
            [
               'Doctor Name'=> $data['doctor']['user']['name'],
               'Diagnosis Name'=> $data['diagnosis_name'],
            ],
            [
                'type' => 'new_diagnosis_notification', 
                'payload' => $data
            ]
        );
    }
}
