<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FetchMedicationController extends Controller
{


    /**
     * Find medication by Id
     */

    public function find($medication_id)
    {
        $medication = Medication::find($medication_id);
        return response()->json($this->convert($medication));
    }
    /**
     * Find medications by patient ID.
     */
    public function findByPatient(Request $request)
    {
        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'per_page' => 'nullable|integer|min:1',
            'page_number' => 'nullable|integer|min:1',
        ];

        try {
            $validatedData = $request->validate($rules);
            return $this->fetchMedications(
                'patient_id',
                $validatedData['patient_id'],
                $validatedData['per_page'] ?? 10,
                $validatedData['page_number'] ?? 1
            );
        } catch (ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed.',
                'details' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Find medications by prescriber ID.
     */
    public function findByDoctor(Request $request)
    {
        $rules = [
            'doctor_id' => 'required|exists:doctors,id',
            'per_page' => 'nullable|integer|min:1',
            'page_number' => 'nullable|integer|min:1',
        ];

        try {
            $validatedData = $request->validate($rules);
            return $this->fetchMedications(
                'doctor_id',
                $validatedData['doctor_id'],
                $validatedData['per_page'] ?? 10,
                $validatedData['page_number'] ?? 1
            );
        } catch (ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed.',
                'details' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Fetch medications based on field and value.
     */
    private function fetchMedications(string $field, $value, $perPage, $pageNumber)
    {
        try {
            $medications = Medication::where($field, $value)
                ->with(['patient.user.profile', 'doctor.user.profile', 'caregiver.user.profile', 'diagnosis', 'form', 'unit', 'route'])
                ->paginate($perPage, ['*'], 'page', $pageNumber);

            return response()->json([
                'error' => false,
                'data' => $this->formatData($medications->items()),
                'pagination' => [
                    'current_page' => $medications->currentPage(),
                    'total_pages' => $medications->lastPage(),
                    'total_items' => $medications->total(),
                    'per_page' => $medications->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while fetching medications.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    static function formatData($medications)
    {
        return collect($medications)->map(function ($medication) {
            return self::convert($medication);
        });
    }

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
