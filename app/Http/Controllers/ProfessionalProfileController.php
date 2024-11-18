<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;

class ProfessionalProfileController extends Controller
{
    public function fetchProfile($id, $model, $type)
    {
        $record = $model::with([
            "user.profile"
        ])->find($id);

        if ($record) {
            return response()->json([
                "error" => false,
                'data' => $record,


            ], 200);
        }

        return response()->json([
            "error" => true,
            "message" => ucfirst($type) . " not found"
        ], 404);
    }

    public function patient($id)
    {
        return $this->fetchProfile($id, Patient::class, 'patient');
    }

    public function doctor($id)
    {
        return $this->fetchProfile($id, Doctor::class, 'doctor');
    }

    public function caregiver($id)
    {
        return $this->fetchProfile($id, Caregiver::class, 'caregiver');
    }
}
