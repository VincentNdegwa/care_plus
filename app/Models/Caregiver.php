<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caregiver extends Model
{
    protected $table = "caregivers";
    protected $fillable = [
        "user_id",
        "specialization",
        "last_activity",
        "agency_name",
        "agency_contact",
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function patients(){
        return $this->hasMany(CaregiverRelation::class);
    }
}
