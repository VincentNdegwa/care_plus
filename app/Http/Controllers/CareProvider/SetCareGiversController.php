<?php

namespace App\Http\Controllers\CareProvider;

use App\Http\Controllers\Controller;
use App\Models\CaregiverRelation;
use App\Models\DoctorRelation;
use Illuminate\Http\Request;

class SetCareGiversController extends Controller
{
    private function setRelation(Request $request, string $role, string $model, array $validationRules)
    {
        try {
            $validatedData = $request->validate($validationRules);

            $relation = $model::updateOrCreate(
                [
                    "{$role}_id" => $validatedData["{$role}_id"],
                    "patient_id" => $validatedData['patient_id'],
                ],
                $validatedData
            );

            return response()->json([
                "error" => false,
                "message" => ucfirst($role) . " relation set successfully.",
                ucfirst($role) => $relation,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "error" => true,
                "message" => $e->getMessage(),
                "errors" => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "An error occurred while setting the relation.",
                "errors" => $e->getMessage(),
            ], 500);
        }
    }

    private function removeRelation($request, $role, $model, $validationRules)
    {

        try {
            $validatedData = $request->validate($validationRules);

            $relation = $model::where([
                "{$role}_id" => $validatedData["{$role}_id"],
                "patient_id" => $validatedData['patient_id'],
            ])->first();
            if ($relation) {
                $relation->delete();
                return response()->json([
                    'error' => false,
                    'message' => ucfirst($role) . ' relation removed',
                ]);
            }
            return response()->json([
                'error' => true,
                'message' => ucfirst($role) . ' relation not found',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "error" => true,
                "message" => $e->getMessage(),
                "errors" => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => "An error occurred while setting the relation.",
                "errors" => $e->getMessage(),
            ], 500);
        }
    }

    public function setDoctor(Request $request)
    {
        $validationRules = [
            "doctor_id" => "required|exists:doctors,id",
            "patient_id" => "required|exists:patients,id",
            "isMain" => "required|boolean",
        ];

        return $this->setRelation($request, 'doctor', DoctorRelation::class, $validationRules);
    }

    public function setCareGiver(Request $request)
    {
        $validationRules = [
            "caregiver_id" => "required|exists:caregivers,id",
            "patient_id" => "required|exists:patients,id",
            "relation" => "required|string",
        ];

        return $this->setRelation($request, 'caregiver', CaregiverRelation::class, $validationRules);
    }
    public function removeDoctor(Request $request)
    {
        $validationRules = [
            "doctor_id" => "required|exists:doctors,id",
            "patient_id" => "required|exists:patients,id",
        ];

        return $this->removeRelation($request, "doctor", DoctorRelation::class, $validationRules);
    }
    public function removeCareGiver(Request $request)
    {
        $validationRules = [
            "caregiver_id" => "required|exists:caregivers,id",
            "patient_id" => "required|exists:patients,id",
        ];

        return $this->removeRelation($request, 'caregiver', CaregiverRelation::class, $validationRules);
    }
}
