<?php

namespace App\Jobs;

use App\Models\Medication;
use App\Models\Schedules\MedicationTracker;
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
        $trakers = MedicationTracker::where(function($query){
            $query->where('stop_date', '<', now())
            ->orWhere('status', 'Stopped');
        })
        ->get();

        foreach ($trakers as $tracker) {
            $medication = $tracker->getMedication();
            $medication->active = false;
            $medication->save();
        }

    }
}
