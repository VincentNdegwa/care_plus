<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaregiverRelation extends Model
{
    protected $table = "caregivers_relations";
    protected $fillable = [
        "patient_id",
        "caregiver_id",
        "relation",
    ];
}
