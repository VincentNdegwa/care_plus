<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use Exception;
use Illuminate\Http\Request;

class UpdateMedicationController extends Controller
{
    public function update(Request $request, $medication_id)
    {
        try {
            $validate = $request->validate([
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
            ]);

            $medication = Medication::find($medication_id);
            if (!$medication) {
                return response()->json([
                    "error" => true,
                    "message" => "Medication not found"
                ], 404);
            }

            $medication->update($request->all());
            $medication->refresh();
            return response()->json([
                "error" => false,
                "message" => "Medication Updated successfully",
                "medication" => $medication
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                "errors" => $th->errors()
            ], 422);
        } catch (Exception $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                "errors" => $th
            ], 500);
        }
    }
}
