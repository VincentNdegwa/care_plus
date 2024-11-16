<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caregiver extends Model
{
    protected $table = "caregivers";
    protected $fillable = [
        "user_id",
        "agency_name",
        "agency_contact",
    ];
}
