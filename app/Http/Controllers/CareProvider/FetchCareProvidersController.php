<?php

namespace App\Http\Controllers\CareProvider;

use App\Http\Controllers\Controller;
use App\Models\CaregiverRelation;
use App\Models\DoctorRelation;
use Illuminate\Http\Request;

class FetchCareProvidersController extends Controller
{
    public function fetchPatientDoctors(Request $request, $patient_id)
    {
        return $this->fetchData(DoctorRelation::class, 'doctor', $patient_id);
    }

    public function fetchPatientCareGivers(Request $request, $patient_id)
    {
        return $this->fetchData(CaregiverRelation::class, 'caregiver', $patient_id);
    }
    private function fetchData($model, $relation, $patient_id)
    {
        $data = $model::where("patient_id", $patient_id)->with($relation)->get();
        return response()->json([
            "data" => $data
        ]);
    }
}
