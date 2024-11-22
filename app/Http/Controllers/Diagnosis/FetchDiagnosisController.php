<?php

namespace App\Http\Controllers\Diagnosis;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Illuminate\Http\Request;

class FetchDiagnosisController extends Controller
{
    public function find($diagnosis_id)
    {
        $diagnosis = Diagnosis::with('patient.user', 'doctor.user')
            ->find($diagnosis_id);

        if ($diagnosis) {
            return response($diagnosis);
        } else {
            return response()->json(['error' => true, 'message' => 'Diagnosis not found'], 404);
        }
    }
    public function fetchByPatient($patientId)
    {
        return $this->fetchDiagnoses(['patient_id' => $patientId], 'No diagnoses found for this patient');
    }


    public function fetchByDoctor($doctorId)
    {
        return $this->fetchDiagnoses(['doctor_id' => $doctorId], 'No diagnoses found for this doctor');
    }


    public function searchDiagnosis(Request $request, $professionalId = null)
    {
        $userId = $professionalId ?? $request->user()->id();
        $search = $request->query('search');

        $diagnoses = Diagnosis::with('patient.user', 'doctor.user')
            ->where(function ($query) use ($userId) {
                $query->where('patient_id', $userId)
                    ->orWhere('doctor_id', $userId);
            })
            ->where(function ($query) use ($search) {
                $query->where('diagnosis_name', 'LIKE', "%{$search}%")
                    ->orWhere('symptoms', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            })
            ->get();

        return $diagnoses->isEmpty()
            ? response()->json(['error' => true, 'message' => 'No diagnoses found matching your search criteria'], 404)
            : response()->json($diagnoses);
    }


    private function fetchDiagnoses(array $filters, string $notFoundMessage)
    {
        $diagnoses = Diagnosis::with('patient.user', 'doctor.user')
            ->where($filters)
            ->get();

        return $diagnoses->isEmpty()
            ? response()->json(['error' => true, 'message' => $notFoundMessage], 404)
            : response()->json($diagnoses);
    }
}
