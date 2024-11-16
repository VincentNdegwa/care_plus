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

    public function doctorRelations()
    {
        return $this->hasMany(DoctorRelation::class, 'patient_id');
    }
    public function caregiverRelations()
    {
        return $this->hasMany(CaregiverRelation::class, 'patient_id');
    }
}
