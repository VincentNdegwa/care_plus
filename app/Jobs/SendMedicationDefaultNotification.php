<?php

namespace App\Jobs;

use App\Models\Notification;
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

        $schedule = $this->schedule->load(['medication']);
        $scheduleArray = $schedule->toArray();
        
        SendNotification::dispatch([$this->userId], $scheduleArray, 'medication_reminder',[
            'notifiable'=>MedicationSchedule::class,
            'notifiable_id'=>$schedule->id
        ]);
    }

}
