<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\CaregiverRelation;
use App\Models\DoctorRelation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FetchPatientCareGiversController extends Controller
{
    public function fetchForDoctor(Request $request)
    {
        $this->validateRequest($request, 'doctor_id', 'doctors');

        $relations = $this->getRelations(
            DoctorRelation::class,
            'doctor_id',
            $request->doctor_id,
            $request->search
        );

        return response()->json($this->format($relations));
    }

    public function fetchForCaregiver(Request $request)
    {
        $this->validateRequest($request, 'caregiver_id', 'caregivers');

        $relations = $this->getRelations(
            CaregiverRelation::class,
            'caregiver_id',
            $request->caregiver_id,
            $request->search
        );

        return response()->json($this->format($relations));
    }

    private function validateRequest(Request $request, string $idField, string $table)
    {
        $request->validate([
            $idField => "required|integer|exists:$table,id",
            'search' => 'nullable|string'
        ]);
    }

    private function getRelations(string $modelClass, string $idField, int $id, ?string $search)
    {
        return $modelClass::where($idField, $id)
            ->with(['patient.user' => function($query) use ($search) {
                $this->applySearchQuery($query, $search);
            }])
            ->whereHas('patient.user', function($query) use ($search) {
                $this->applySearchQuery($query, $search);
            })
            ->get();
    }

    private function applySearchQuery($query, ?string $search)
    {
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
    }

    private function format($data)
    {
        return [
            'data' => $data->map(function($item) {
                return [
                    'patient' => $item->patient,
                ];
            })
        ];
    }
}
