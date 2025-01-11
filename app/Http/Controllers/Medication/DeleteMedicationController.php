<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use Illuminate\Http\Request;

class DeleteMedicationController extends Controller
{
    public function delete($medication_id)
    {
        try {
            $medication = Medication::where('id', $medication_id)->first();
            if (!$medication) {
                return response()->json([
                    'error' => true,
                    'message' => "Medication not found"
                ], 404);
            }
            $medication->delete();

            return response()->json([
                "error" => false,
                "message" => "Medication Deleted Successfully"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "error" => false,
                "message" => $th->getMessage(),
                "errors" => $th,
            ], 500);
        }
    }
}
