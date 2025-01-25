<?php

namespace App\Models;

use App\Models\Schedules\MedicationSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = "patients";
    protected $fillable = [
        "user_id",
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function doctorRelations()
    {
        return $this->hasMany(DoctorRelation::class, 'patient_id');
    }
    public function caregiverRelations()
    {
        return $this->hasMany(CaregiverRelation::class, 'patient_id');
    }

    public function diagnoses()
    {
        return $this->hasMany(Diagnosis::class, 'patient_id');
    }

    public function medications()
    {
        return $this->hasMany(Medication::class, 'patient_id');
    }
    public function todaySchedules()
    {
        $now = Carbon::now();
        $start_day = $now->copy()->startOfDay();
        $end_day = $now->copy()->endOfDay();

        $medications = MedicationSchedule::whereBetween('dose_time', [$start_day, $end_day])
            ->where("patient_id", $this->id)
            ->with('medication')
            ->get();

        return [
            "now" => $now,
            "start_day" => $start_day,
            "end_day" => $end_day,
            "count" => $medications->count(),
            "medications" => $medications,
        ];
    }
}
