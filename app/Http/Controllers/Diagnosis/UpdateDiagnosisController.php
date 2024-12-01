<?php

namespace App\Http\Controllers\Diagnosis;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Exception;
use Illuminate\Http\Request;

class UpdateDiagnosisController extends Controller
{
    public function update(Request $request, $diagnosis_id)
    {
        try {
            $validateData = $request->validate([
                "diagnosis_name" => "sometimes|string",
                "description" => "sometimes|string",
                "symptoms" => "sometimes|string",
                "date_diagnosed" => "sometimes|date",
            ]);

            $diagnosis = Diagnosis::findOrFail($diagnosis_id);
            $diagnosis->update($validateData);

            return response()->json([
                "error" => false,
                "message" => "Diagnosis updated successfully",
                "data" => $diagnosis
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "error" => true,
                "message" => $e->getMessage(),
                "errors" => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "An error occurred while updating the diagnosis",
                "details" => $e->getMessage()
            ], 500);
        }
    }
}
