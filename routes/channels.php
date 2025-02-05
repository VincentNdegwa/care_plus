<?php

use App\Models\Patient;
use App\Models\Schedules\MedicationSchedule;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('medication.take.{patientId}', function (Patient $patient, $patientId) {
    if ((int) $patient->id !== (int) $patientId) {
        return false;
    }
    
    // if (!$patient->is_active) {
    //     return false;
    // }
    
    return true;
});
