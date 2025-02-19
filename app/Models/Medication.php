<?php

namespace App\Models;

use App\Models\Medication\MedicationForm;
use App\Models\Medication\MedicationRoute;
use App\Models\Medication\MedicationUnit;
use App\Models\Schedules\MedicationTracker;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $table = "medications";
    protected $fillable = [
        'patient_id',
        'diagnosis_id',
        'medication_name',
        'dosage_quantity',
        'dosage_strength',
        'form_id',
        'unit_id',
        'route_id',
        'frequency',
        'duration',
        'prescribed_date',
        'doctor_id',
        'caregiver_id',
        'stock',
        'active',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    public function caregiver()
    {
        return $this->belongsTo(Caregiver::class, 'caregiver_id');
    }

    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class, 'diagnosis_id');
    }

    public function form()
    {
        return $this->belongsTo(MedicationForm::class, 'form_id');
    }

    public function unit()
    {
        return $this->belongsTo(MedicationUnit::class, 'unit_id');
    }
    public function route()
    {
        return $this->belongsTo(MedicationRoute::class, 'route_id');
    }

    public function sideEffects()
    {
        return $this->hasMany(SideEffect::class, 'medication_id');
    }

    public function tracker()
    {
        return $this->hasOne(MedicationTracker::class);
    }

    public function hasRunningSchedule()
    {
        return $this->tracker()->where('status', '!=', 'Stopped')->exists();
    }
    public function isActive(): int
    {
        return (int) ($this->hasRunningSchedule() && $this->active);
    }
    public function trackerStatus(){
        return $this->tracker->status;
    }
}
