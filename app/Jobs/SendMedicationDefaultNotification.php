<?php

namespace App\Jobs;

use App\Models\Schedules\MedicationSchedule;
use App\Models\Schedules\MedicationScheduleNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMedicationDefaultNotification implements ShouldQueue
{
    use Queueable;

    public $schedule;

    /**
     * Create a new job instance.
     */
    public function __construct($schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {


        MedicationScheduleNotification::create([
            'medication_schedule_id' => $this->schedule->id,
            'message' => "
                     
                     It's time to take your medication:      
                     Please take it as prescribed 
                     
                     Take care,  
                     ",
            'status' => 'Pending',
        ]);
    }
}
