<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotification;
use App\MedicationConvert;
use App\Models\Medication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateMedicationController extends Controller
{
    use MedicationConvert;

    public function create(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        $doctor_id = ($role === "Doctor" && $user->doctor) ? $user->doctor->id : null;
        $caregiver_id = ($role === "Caregiver" && $user->caregiver) ? $user->caregiver->id : null;

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'diagnosis_id' => 'nullable|exists:diagnoses,id',
            "medication_name" => 'required|string',
            'dosage_quantity' => 'required|string|max:255',
            'dosage_strength' => 'required|string|max:255',
            'duration' => 'required|string|max:255', // Example: "7 days"
            'form_id' => 'nullable|exists:medication_forms,id',
            'route_id' => 'nullable|exists:medication_routes,id',
            'frequency' => 'required|string|max:255', // Example: "2 times per day"
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
            $validatedData['active'] = 0;


            $newMedication = Medication::create($validatedData);
            $medication = Medication::where("id", $newMedication->id)
                ->with(['patient.user.profile', 'doctor.user.profile', 'caregiver.user.profile', 'diagnosis', 'form', 'unit', 'route'])
                ->first();
            $converted = $this::convert($medication);

            SendNotification::dispatch(
                [$request->patient_id],
                $validatedData,
                "new_prescription_added",
                [
                    'notifiable' => Medication::class,
                    'notifiable_id' => $medication->id
                ]
            );

            return response()->json([
                "error" => false,
                "message" => "Medication record created successfully.",
                "data" => $converted
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
}
