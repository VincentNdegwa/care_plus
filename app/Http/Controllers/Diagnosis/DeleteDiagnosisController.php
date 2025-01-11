<?php

namespace App\Http\Controllers\Diagnosis;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Illuminate\Http\Request;

class DeleteDiagnosisController extends Controller
{
    public function delete($diagnosis_id)
    {
        try {
            $diagnosis = Diagnosis::where('id', $diagnosis_id)->first();
            if (!$diagnosis) {
                return response()->json([
                    'error' => true,
                    'message' => "Diagnosis not found"
                ], 404);
            }
            $diagnosis->delete();

            return response()->json([
                "error" => false,
                "message" => "Diagnosis Deleted Successfully"
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
