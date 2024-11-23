<?php

namespace App\Models\Medication;

use Illuminate\Database\Eloquent\Model;

class MedicationForm extends Model
{
    protected $table = "medication_forms";
    protected $fillable = [
        "patient_id",
        "name",
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
