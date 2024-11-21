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
        return response($diagnosis);
    }
}
