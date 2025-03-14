<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Medication;

class MedicationTracker extends Model
{
    use HasFactory;

    protected $table = 'medication_tracker';

    protected $fillable = [
        'medication_id',
        'start_date',
        'end_date',
        'next_start_month',
        'stop_date',
        'duration',
        'frequency',
        'schedules',
        'timezone',
        'status',
        'stopped_when'
    ];

    /**
     * Get the medication that owns the tracker.
     */
    public function medication()
    {
        return $this->belongsTo(Medication::class, 'medication_id');
    }
    public function getMedication(){
        return Medication::find($this->medication_id);
    }
}
