<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthVital extends Model
{
    protected $table = 'health_vitals';
    protected $fillable = [
        'patient_id',
        'vital_data'
    ];
    protected $casts = [
        'vital_data' => 'array'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
