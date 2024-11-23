<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateMedicationController extends Controller
{

    public function create(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        $doctor_id = ($role === "Doctor" && $user->doctorProfile) ? $user->doctorProfile->id : null;
        $caregiver_id = ($role === "Caregiver" && $user->caregiverProfile) ? $user->caregiverProfile->id : null;

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'diagnosis_id' => 'nullable|exists:diagnoses,id',
            "medication_name" => 'required|string',
            'dosage_quantity' => 'required|string|max:255',
            'dosage_strength' => 'required|string|max:255',
            'form_id' => 'nullable|exists:medication_forms,id',
            'route_id' => 'nullable|exists:medication_routes,id',
            'frequency' => 'required|string|max:255', // Example: "2 times per day"
            'duration' => 'nullable|string|max:255', // Example: "7 days"
            'prescribed_date' => 'nullable|date',
            'stock' => 'nullable|integer|min:0',
        ];

        try {
            $validatedData = $request->validate($rules);
            $validatedData['doctor_id'] = $doctor_id;
            $validatedData['caregiver_id'] = $caregiver_id;

            if (!isset($validatedData['prescribed_date'])) {
                $validatedData['prescribed_date'] = Carbon::now()->format('Y-m-d H:i:s');
            }


            $medication = Medication::create($validatedData);

            return response()->json([
                "error" => false,
                "message" => "Medication record created successfully.",
                "data" => $medication
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                "error" => true,
                "message" => $e->getMessage(),
                "errors" => $e->errors()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "An error occurred while creating the medication record.",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    function generateMedicationScheduleWithStock($frequency, $duration, $startDate, $stock)
    {
        // Parse the start date
        $startDate = Carbon::parse($startDate);
        // Calculate the end date based on duration
        $endDate = $startDate->copy()->addDays($duration); // Assuming duration is in days

        // Get the frequency interval in minutes
        $frequencyIntervals = $this->getFrequencyInterval($frequency);

        $medicationLogs = [];
        $currentDoseTime = $startDate;

        // Loop through and generate the medication logs
        while ($currentDoseTime->lte($endDate) && $stock > 0) {

            // Decrease stock
            $stock--;

            $medicationLogs[] = [
                'dose_time' => $currentDoseTime,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            // Move to the next dose time based on frequency
            if ($frequency != "Once a day" && $frequency != "Twice a day") {
                $currentDoseTime->addMinutes($frequencyIntervals);
            } else {
                $currentDoseTime->addDays(1); // For Once a day, move to the next day
            }
        }

        // Insert all medication logs into the database
    }
    function generateMedicationScheduleWithoutStock($frequency, $duration, $startDate)
    {
        // Parse the start date
        $startDate = Carbon::parse($startDate);
        // Calculate the end date based on duration
        $endDate = $startDate->copy()->addDays($duration); // Assuming duration is in days

        // Get the frequency interval in minutes
        $frequencyIntervals = $this->getFrequencyInterval($frequency);

        $medicationLogs = [];
        $currentDoseTime = $startDate;

        // Loop through and generate the medication logs without considering stock
        while ($currentDoseTime->lte($endDate)) {

            $medicationLogs[] = [
                'dose_time' => $currentDoseTime,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            // Move to the next dose time based on frequency
            if ($frequency != "Once a day" && $frequency != "Twice a day") {
                $currentDoseTime->addMinutes($frequencyIntervals);
            } else {
                $currentDoseTime->addDays(1); // For Once a day, move to the next day
            }
        }

        // Insert all medication logs into the database
    }
    function getFrequencyInterval($frequency)
    {
        // Return the interval in minutes based on the frequency string
        switch ($frequency) {
            case "Once a day":
                return 24 * 60; // 24 hours
            case "Twice a day":
                return 12 * 60; // 12 hours
            case "Three times a day":
                return 8 * 60; // 8 hours
            case "Four times a day":
                return 6 * 60; // 6 hours
            case "Once every other day":
                return 48 * 60; // 48 hours
            case "Every third day":
                return 72 * 60; // 72 hours
            case "Once a week":
                return 7 * 24 * 60; // 7 days
            case "Twice a week":
                return 3 * 24 * 60; // 3 days
            case "Three times a week":
                return 2 * 24 * 60; // 2 days
            case "Once a month":
                return 30 * 24 * 60; // 30 days
            case "Every 6 hours":
                return 6 * 60; // 6 hours
            case "Every 8 hours":
                return 8 * 60; // 8 hours
            case "Every 12 hours":
                return 12 * 60; // 12 hours
            case "Every 24 hours":
                return 24 * 60; // 24 hours
            case "Every hour":
                return 60; // 1 hour
            case "Every 2 hours":
                return 2 * 60; // 2 hours
            case "Every 4 hours":
                return 4 * 60; // 4 hours
            case "Every 6 hours":
                return 6 * 60; // 6 hours
            case "Every 30 minutes":
                return 30; // 30 minutes
            case "Every 45 minutes":
                return 45; // 45 minutes
            case "On demand":
                return 0; // No fixed schedule
            case "As needed":
                return 0; // No fixed schedule
            case "Until finished":
                return 0; // No fixed schedule
            case "Until bottle is empty":
                return 0; // No fixed schedule
            default:
                return 24 * 60; // Default to "Once a day"
        }
    }
}
