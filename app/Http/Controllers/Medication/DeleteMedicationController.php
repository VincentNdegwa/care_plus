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
            $medication = Medication::find($medication_id);
            if (!$medication) {
                return response("Medication not found", 404);
            }
    
            if ($medication->hasRunningSchedule()) {
                return response("Medication has running schedules, stop them first", 400);
            }
    
            $medication->delete();
            return response()->json([
                "error" => false,
                "message" => "Medication Deleted Successfully"
            ], 200);
            
        } catch (\Throwable $th) {
            return response($th->getMessage(), 500);
        }
    }
    
}
