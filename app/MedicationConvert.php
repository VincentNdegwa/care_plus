<?php

namespace App;

trait MedicationConvert
{
    static function convert($medication)
    {
        return [
            'id' => $medication->id,
            'patient' => $medication->patient ? [
                "patient_id" => $medication->patient->id,
                "name" => $medication->patient->user->name,
                "email" => $medication->patient->user->email,
                "avatar" => $medication->patient->user->profile->avatar,
            ] : null,
            'medication_name' => $medication->medication_name,
            'dosage_quantity' => $medication->dosage_quantity,
            'dosage_strength' => $medication->dosage_strength,
            'form' => $medication->form,
            'route' => $medication->route,
            'frequency' => $medication->frequency,
            'duration' => $medication->duration,
            'prescribed_date' => $medication->prescribed_date,
            'doctor' => $medication->doctor ? [
                "doctor_id" => $medication->doctor->id,
                "name" => $medication->doctor->user->name ?? null,
                "email" => $medication->doctor->user->email ?? null,
                "avatar" => $medication->doctor->user->profile->avatar ?? null,
            ] : null,
            'caregiver' => $medication->caregiver ? [
                "caregiver_id" => $medication->caregiver->id,
                "name" => $medication->caregiver->user->name,
                "email" => $medication->caregiver->user->email,
                "avatar" => $medication->caregiver->user->profile->avatar,
            ] : null,
            'stock' => $medication->stock,
            'active' => $medication->active,
            'diagnosis' => $medication->diagnosis,
        ];
    }
}
