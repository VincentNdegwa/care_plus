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
}
