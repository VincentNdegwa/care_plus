<?php

namespace App\Http\Controllers\Diagnosis;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Illuminate\Http\Request;

class FetchDiagnosisController extends Controller
{
    private $default_per_page = 10;
    private $default_page_number = 1;
    public function fetchByPatient($patientId, Request $request)
    {
        return $this->fetchDiagnoses(['patient_id' => $patientId], 'No diagnoses found for this patient', $request);
    }

    public function fetchByDoctor($doctorId, Request $request)
    {
        return $this->fetchDiagnoses(['doctor_id' => $doctorId], 'No diagnoses found for this doctor', $request);
    }

    private function fetchDiagnoses(array $filters, string $notFoundMessage, Request $request)
    {
        $this->default_per_page = $request->query('per_page', $this->default_per_page);
        $this->default_page_number = $request->query('page_number', $this->default_page_number);
        $diagnoses = Diagnosis::with('patient.user', 'doctor.user')
            ->where($filters);

        return $this->paginateQuery($diagnoses);
    }
    public function find($diagnosis_id)
    {
        $diagnosis = Diagnosis::with('patient.user', 'doctor.user')
            ->find($diagnosis_id);

        if ($diagnosis) {
            return response()->json($this->formatDiagnosis($diagnosis));
        } else {
            return response()->json(['error' => true, 'message' => 'Diagnosis not found'], 404);
        }
    }
    public function searchDiagnoses(Request $request, $professionalId = null)
    {
        $userId = $professionalId ?? $request->user()->id();
        $search = $request->query('search');
        $this->default_per_page = $request->query('per_page', $this->default_per_page);
        $this->default_page_number = $request->query('page_number', $this->default_page_number);

        $diagnoses = Diagnosis::with('patient.user', 'doctor.user')
            ->where(function ($query) use ($userId) {
                $query->where('patient_id', $userId)
                    ->orWhere('doctor_id', $userId);
            })
            ->where(function ($query) use ($search) {
                $query->where('diagnosis_name', 'LIKE', "%{$search}%")
                    ->orWhere('symptoms', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });

        return $this->paginateQuery($diagnoses);
    }
    public function filterDiagnoses(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'date_from' => 'nullable|date|before:date_to',
                'date_to' => 'nullable|date|after:date_from',
                'diagnosis_name' => 'nullable|string',
                'patient_id' => 'nullable|integer|required_without:doctor_id',
                'doctor_id' => 'nullable|integer|required_without:patient_id'
            ]);

            $diagnosesQuery = Diagnosis::with('patient.user', 'doctor.user');

            if (isset($validatedData['date_from']) && isset($validatedData['date_to'])) {
                $diagnosesQuery->whereBetween('date_diagnosed', [$validatedData['date_from'], $validatedData['date_to']]);
            }
            if (isset($validatedData['diagnosis_name'])) {
                $diagnosesQuery->where('diagnosis_name', $validatedData['diagnosis_name']);
            }
            if (isset($validatedData['patient_id'])) {
                $diagnosesQuery->where('patient_id', $validatedData['patient_id']);
            }
            if (isset($validatedData['doctor_id'])) {
                $diagnosesQuery->where('doctor_id', $validatedData['doctor_id']);
            }

            return $this->paginateQuery($diagnosesQuery);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()],422);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()],500);
        }
    }

    private function paginateQuery($query)
    {
        $diagnoses = $query->paginate($this->default_per_page, ["*"], 'page', $this->default_page_number);
        return response()->json([
            'error' => false,
            'data' => $this->formatDiagnoses($diagnoses->items()),
            'pagination' => [
                'current_page' => $diagnoses->currentPage(),
                'total_pages' => $diagnoses->lastPage(),
                'total_items' => $diagnoses->total(),
                'per_page' => $diagnoses->perPage(),
            ]
        ]);
    }

    private function formatDiagnosis($diagnosis)
    {
        return [
            'id' => $diagnosis->id,
            'diagnosis_name' => $diagnosis->diagnosis_name,
            'description' => $diagnosis->description,
            'date_diagnosed' => $diagnosis->date_diagnosed,
            'patient' => [
                'id' => $diagnosis->patient->id,
                'name' => $diagnosis->patient->user->name,
                "email" => $diagnosis->patient->user->email
            ],
            'doctor' => [
                'id' => $diagnosis->doctor->id,
                'name' => $diagnosis->doctor->user->name,
                "email" => $diagnosis->doctor->user->email
            ],
            "medication_counts"=> $diagnosis->medications->count(),
        ];
    }

    private function formatDiagnoses($diagnoses)
    {
        return collect($diagnoses)->map(function ($diagnosis) {
            return $this->formatDiagnosis($diagnosis);
        });
    }
}
