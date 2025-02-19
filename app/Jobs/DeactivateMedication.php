<?php

namespace App\Jobs;

use App\Models\Medication;
use App\Models\Schedules\MedicationTracker;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeactivateMedication implements ShouldQueue
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
        $stopingMedication = MedicationTracker::where('stop_date', '<', Carbon::now())->get();
        $stopingMedication->each(function($tracker){
            $medication = $tracker->getMedication();
            $medication->active = false;
            $tracker->status = 'Expired';
            
            $tracker->save();
            $medication->save();
        });
    }
}
