<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SideEffect extends Model
{
    protected $table = "side_effects";
    protected $fillable = [
        "medication_id",
        'patient_id',
        "datetime",
        "side_effect",
        "severity",
        "duration",
        "notes",
    ];

    public function medication()
    {
        return $this->belongsTo(Medication::class, 'medication_id');
    }
}
