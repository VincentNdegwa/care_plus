<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MedicationFilterController extends Controller
{
    /**
     * Filter medications based on various criteria.
     */
    public function filterMedications(Request $request)
    {
        $user = $request->user();
        $role = $user->role;
        $rules = [
            'patient_id' => 'nullable|exists:patients,id',
            'diagnosis_id' => 'nullable|exists:diagnoses,id',
            'medication_name' => 'nullable|string',
            'form_id' => 'nullable|exists:medication_forms,id',
            'unit_id' => 'nullable|exists:medication_units,id',
            'route_id' => 'nullable|exists:medication_routes,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'caregiver_id' => 'nullable|exists:caregivers,id',
            'frequency' => 'nullable|string',
            'duration' => 'nullable|string',
            'active' => 'nullable|boolean',
            'stock_min' => 'nullable|integer|min:0',
            'stock_max' => 'nullable|integer|min:0',
            'prescribed_date_from' => 'nullable|date',
            'prescribed_date_to' => 'nullable|date|after_or_equal:prescribed_date_from',
            'per_page' => 'nullable|integer|min:1',
            'page_number' => 'nullable|integer|min:1',
        ];

        try {
            $validatedData = $request->validate($rules);

            $query = Medication::query();
            if ($role === 'Doctor') {
                $query->where('doctor_id', $user->doctor->id);
            } elseif ($role === 'Caregiver') {
                $query->where('caregiver_id', $user->caregiver->id);
            } elseif ($role === 'Patient') {
                $query->where('patient_id', $user->patient->id);
            }


            foreach ($validatedData as $key => $value) {
                if ($value === null) {
                    continue;
                }

                switch ($key) {
                    case 'stock_min':
                        $query->where('stock', '>=', $value);
                        break;
                    case 'stock_max':
                        $query->where('stock', '<=', $value);
                        break;
                    case 'prescribed_date_from':
                        $query->whereDate('prescribed_date', '>=', $value);
                        break;
                    case 'prescribed_date_to':
                        $query->whereDate('prescribed_date', '<=', $value);
                        break;
                    default:
                        $query->where($key, $value);
                        break;
                }
            }

            $perPage = $validatedData['per_page'] ?? 10;
            $pageNumber = $validatedData['page_number'] ?? 1;
            $medications = $query
                ->with(['patient.user.profile', 'doctor.user.profile', 'caregiver.user.profile', 'diagnosis', 'form', 'unit', 'route'])
                ->withCount('sideEffects')
                ->paginate($perPage, ['*'], 'page', $pageNumber);

            return response()->json([
                'error' => false,
                'data' => FetchMedicationController::formatData($medications->items()),
                'pagination' => [
                    'current_page' => $medications->currentPage(),
                    'total_pages' => $medications->lastPage(),
                    'total_items' => $medications->total(),
                    'per_page' => $medications->perPage(),
                ],
                "role" => $user->id
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred while filtering medications.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

}
