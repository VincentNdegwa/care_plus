<?php

use App\Models\Patient;
use App\Models\Schedules\MedicationSchedule;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Broadcast::channel('medication.take.{patientId}', function (Patient $patient, $patientId) {
//     if ((int) $patient->id !== (int) $patientId) {
//         return false;
//     }    
//     return true;
// });
Broadcast::channel('medication.take.{patientId}', function ($user, $patientId) {
    Log::info('Channel authorization attempt', [
        'user' => $user->toArray(),
        'patient_id' => $patientId
    ]);
    
    return (int) $user->id === (int) $patientId;
});
