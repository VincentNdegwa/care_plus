<?php

use App\Models\Patient;
use App\Models\Schedules\MedicationSchedule;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('medication.take.{patientId}', function (Patient $patient, $patientId) {
    // Basic authorization - patient can only listen to their own channel
    if ((int) $patient->id !== (int) $patientId) {
        return false;
    }
    
    // Additional checks if needed
    if (!$patient->is_active) {
        return false;
    }
    
    // You could also check if the patient has any medication schedules
    // if (!$patient->medicationSchedules()->exists()) {
    //     return false;
    // }
    
    return true;
});
