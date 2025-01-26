<?php

namespace App\Models;

use App\Models\Schedules\MedicationSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

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
    public function todaySchedules($todayDate = null)
    {
        $date = $todayDate ?? Carbon::now();

        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $medications = MedicationSchedule::whereBetween('dose_time', [$startOfDay, $endOfDay])
            ->where('patient_id', $this->id)
            ->with('medication')
            ->get();

        return [
            'now' => Carbon::now(),
            'start_of_day' => $startOfDay,
            'end_of_day' => $endOfDay,
            'count' => $medications->count(),
            'medications' => $medications,
        ];
    }
}
