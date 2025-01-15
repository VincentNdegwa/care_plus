<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\MedicationSchedule;

class MedicationScheduleNotification extends Model
{
    use HasFactory;

    protected $table = 'medication_schedules_notifications';

    protected $fillable = [
        'medication_schedule_id',
        'message',
        'status',
    ];

    /**
     * Get the medication schedule that owns the notification.
     */
    public function medicationSchedule()
    {
        return $this->belongsTo(MedicationSchedule::class, 'medication_schedule_id');
    }
}
