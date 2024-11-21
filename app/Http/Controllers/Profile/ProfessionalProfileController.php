<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;

class ProfessionalProfileController extends Controller
{
    public function fetchProfile($id, $model, $type)
    {
        $relations = [
            "user.profile",
        ];
        $record = $model::with($relations)->find($id);
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


    public function doctor($id)
    {
        return $this->fetchProfile($id, Doctor::class, 'doctor');
    }

    public function caregiver($id)
    {
        return $this->fetchProfile($id, Caregiver::class, 'caregiver');
    }
}
