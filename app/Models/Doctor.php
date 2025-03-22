<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $table = "doctors";
    protected $fillable = [
        "user_id",
        "specialization",
        "last_activity",
        "active",
        "license_number",
        "license_issuing_body",
        "clinic_name",
        "clinic_address",

    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
    
}
