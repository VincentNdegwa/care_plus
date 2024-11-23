<?php

namespace App\Models;

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

    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class, 'diagnosis_id');
    }

    public function prescribedBy()
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }
}
