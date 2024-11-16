<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = "patients";
    protected $fillable = [
        "user_id",
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    // public function mainDoctor()
    // {
    //     return $this->belongsTo(User::class, "main_doctor_id");
    // }

    // public function caregiver()
    // {
    //     return $this->belongsTo(User::class, "caregiver_id");
    // }
}
