<?php

namespace App\Models\Medication;

use Illuminate\Database\Eloquent\Model;

class MedicationUnit extends Model
{
    protected $table = "medication_units";

    protected $fillable = [
        "patient_id",
        "name",
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
