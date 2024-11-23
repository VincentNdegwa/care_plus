<?php

namespace App\Models\Medication;

use Illuminate\Database\Eloquent\Model;

class MedicationRoute extends Model
{
    protected $table = "medication_routes";
    protected $fillable = [
        "name",
        "description",
    ];
}
