<?php

namespace App\Http\Controllers\Diagnosis;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Illuminate\Http\Request;

class CreateDiagnosisController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validateDate = $request->validate([
                "patient_id" => "required|exists:patients,id",
                "diagnosis_name" => "required",
                "description" => "nullable|string",
                "symptoms" => "nullable|string",
                "date_diagnosed" => "required|date",
            ]);

            $id = $request->user()->doctorProfile->id;
            $validateDate["doctor_id"] = $id;

            $diagnosis = Diagnosis::create($validateDate);
            $diagnosisWithRelationship = Diagnosis::where("id", $diagnosis->id)
                ->with('patient.user', 'doctor.user')
                ->first();
            return response()->json([
                "error" => false,
                "message" => "Diagnosis created successfully",
                "data" => $diagnosisWithRelationship
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                'errors' => $th->errors()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                'message' => 'An error occurred while creating the diagnosis',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
