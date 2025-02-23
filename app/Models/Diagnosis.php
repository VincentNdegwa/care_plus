<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    protected $table = "diagnoses";
    protected $fillable = [
        "patient_id",
        "diagnosis_name",
        "description",
        "symptoms",
        "date_diagnosed",
        "doctor_id",
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
    public function medications()
    {
        return $this->hasMany(Medication::class, 'diagnosis_id');
    }
    public function medicationCount()
    {
        return $this->medications()->count();
    }

}
