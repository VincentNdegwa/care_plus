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
}
