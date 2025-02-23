<?php

namespace App\Http\Controllers\Diagnosis;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotification;
use App\Models\Diagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            $id = $request->user()->doctor->id;
            $validateDate["doctor_id"] = $id;

            $diagnosis = Diagnosis::create($validateDate);
            $diagnosisWithRelationship = Diagnosis::where("id", $diagnosis->id)
                ->with('patient.user', 'doctor.user')
                ->first();
            $diagnosisWithRelationship->medication_counts = 0;

            $diagnosisData = $this->formatData($diagnosisWithRelationship);

            SendNotification::dispatch(
                [$diagnosisWithRelationship->patient->user->id], 
                $diagnosisData, 
                'new_diagnosis_notification'
            );
           
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
            Log::error($e);
            return response()->json([
                "error" => true,
                'message' => 'An error occurred while creating the diagnosis',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public static function formatData($data)
    {
        return [
            'id' => $data->id,
            'diagnosis_name' => $data->diagnosis_name,
            'description' => $data->description,
            'date_diagnosed' => $data->date_diagnosed,
            'patient' => [
                'id' => $data->patient->user->id,
                'name' => $data->patient->user->name,
                'email' => $data->patient->user->email
            ],
            'doctor' => [
                'id' => $data->doctor->user->id,
                'name' => $data->doctor->user->name,
                'email' => $data->doctor->user->email
            ],
            'medication_counts' => 0
        ];
    }
}
