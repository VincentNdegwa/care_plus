<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Medication;
use App\Models\Patient;

class MedicationSchedule extends Model
{
    use HasFactory;

    protected $table = 'medication_schedules';

    protected $fillable = [
        'medication_id',
        'patient_id',
        'dose_time',
        'status'
    ];

    /**
     * Get the medication that owns the schedule.
     */
    public function medication()
    {
        return $this->belongsTo(Medication::class, 'medication_id');
    }

    /**
     * Get the patient that owns the schedule.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }


    /**
     * Get the notifications for the medication schedule.
     */
    public function notifications()
    {
        return $this->hasMany(MedicationScheduleNotification::class, 'medication_schedule_id');
    }

}
