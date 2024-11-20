<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $table = "medications";
    protected $fillable = [
        "patient_id",
        "diagnosis_id",
        "medication_name",
        "dosage",
        "frequency",
        "duration",
        "prescribed_date",
        "prescribed_by",
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
