<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateProfessionalProfileController extends Controller
{
    /**
     * Generic update method for updating a profile.
     */
    private function updateProfile(Request $request, string $role, string $model, array $validationRules)
    {
        $user = $request->user();

        // Check if the user has the correct role
        if ($user->role !== $role) {
            return response()->json(['error' => "You don't have permission to update this profile"], 403);
        }

        // Validate the request data
        $validatedData = $request->validate($validationRules);

        // Update or create the profile
        $model::updateOrCreate(
            ['user_id' => $user->id],
            $validatedData
        );

        return response()->noContent();
    }

    /**
     * Update doctor's profile.
     */
    public function doctor(Request $request)
    {
        $validationRules = [
            "specialization" => 'required|string',
            "qualifications" => 'required|string',
            "active" => 'required|boolean',
            "license_number" => [
                'required',
                'string',
                Rule::unique('doctors', 'license_number'),
            ],
            "license_issuing_body" => 'required|string',
            "clinic_name" => 'required|string',
            "clinic_address" => 'required|string',
        ];

        return $this->updateProfile($request, 'Doctor', Doctor::class, $validationRules);
    }

    /**
     * Update patient's profile.
     */
    public function patient(Request $request)
    {
        $validationRules = [
            // Add any required patient validation rules here
        ];

        return $this->updateProfile($request, 'Patient', Patient::class, $validationRules);
    }

    /**
     * Update caregiver's profile.
     */
    public function caregiver(Request $request)
    {
        $validationRules = [
            "specialization" => "nullable|string",
            "agency_name" => "required|string",
            "agency_contact" => "required|string",
        ];

        return $this->updateProfile($request, 'Caregiver', Caregiver::class, $validationRules);
    }
}
