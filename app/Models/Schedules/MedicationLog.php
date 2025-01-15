<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\MedicationSchedule;

class MedicationLog extends Model
{
    use HasFactory;

    protected $table = 'medication_logs';

    protected $fillable = [
        'medication_schedule_id',
        'status',
    ];

    /**
     * Get the medication schedule that owns the log.
     */
    public function medicationSchedule()
    {
        return $this->belongsTo(MedicationSchedule::class, 'medication_schedule_id');
    }
}
