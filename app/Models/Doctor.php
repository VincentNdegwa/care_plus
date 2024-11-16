<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $table = "doctors";
    protected $fillable = [
        "user_id",
        "specialization",
        "qualifications",
        "license_number",
        "license_issuing_body",
        "clinic_address",
    ];
}
