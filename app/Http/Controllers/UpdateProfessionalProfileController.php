<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateProfessionalProfileController extends Controller
{

    private function updateProfile(Request $request, string $role, string $model, array $validationRules)
    {
        try {
            $user = $request->user();

            if ($user->role !== $role) {
                return response()->json(['error' => true, 'message' => "You don't have permission to update this profile"], 403);
            }

            $validatedData = $request->validate($validationRules);

            $model::updateOrCreate(
                ['user_id' => $user->id],
                $validatedData
            );

            $prof = new ProfessionalProfileController();
            $response = $prof->fetchProfile($user->id, $model, $role);
            if ($response['error']) {
                return response()->json([
                    "error" => true,
                    "message" => "Failed to update profile"
                ]);
            }

            return response()->json([
                "error" => false,
                "message" => "Profile Updated successfully",
                "data" => $response['data']
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                'errors' => $th->errors()
            ]);
        } catch (\Exception $th) {
            return response()->json([
                "error" => true,
                "message" => "An error occurred while updating the profile",
                'errors' => $th->getMessage()
            ]);
        }
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
                Rule::unique('doctors', 'license_number')->ignore($request->user()->id, 'user_id'),
            ],
            "license_issuing_body" => 'required|string',
            "clinic_name" => 'required|string',
            "clinic_address" => 'required|string',
        ];

        return $this->updateProfile($request, 'Doctor', Doctor::class, $validationRules);
    }

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
