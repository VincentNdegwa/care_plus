<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorRelation extends Model
{
    protected $table = "doctors_relations";
    protected $fillable = [
        "patient_id",
        "doctor_id",
        "isMain",
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}