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
        'processed_at',
        'dose_time',
        'status',
        'taken_at',
        'second_notification_sent'
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

    /**
     * Get the snoozes for the medication schedule.
     */
    public function snoozes()
    {
        return $this->hasMany(MedicationSnooze::class);
    }

    /**
     * Check if the medication schedule has an active snooze.
     */
    public function hasActiveSnooze(): bool
    {
        return $this->snoozes()
            ->where('status', '!=', 'Dismissed')
            ->exists();
    }

    public function hasActiveMedication():bool
    {
        return $this->medication()->where('status', 1)->exists();
    }

    public function scheduleIsRunning() : bool {

        return $this->medication()->trackers()->where('status', '!=', 'Stopped')->exists();
    }
}
