<?php

use App\Models\Patient;
use App\Models\Schedules\MedicationSchedule;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('medication.take.{scheduleId}', function (Patient $patient, $scheduleId) {
    $medicationSchedule = MedicationSchedule::with('patient')->find($scheduleId);

    if ($medicationSchedule && $patient->id === $medicationSchedule->patient->id) {
        return true;
    }

    return false;
});
