<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Service\Scheduler\ScheduleGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleMedicationController extends Controller
{
    public function scheduleDefault(Request $request)
    {
        try {
            $validateData = $request->validate([
                "medication_id" => "required|exists:medications,id",
                "start_datetime" => "date_format:Y-m-d H:i:s"
            ]);

            return ScheduleGenerator::generateSchedule($validateData);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                'error' => true,
                "message" => $th->getMessage(),
                'errors' => $th->errors()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e,
            ], 500);
        }
    }

    public function scheduleCustom(Request $request)
    {
        try {
            $validatedData = $request->validate([
                "medication_id" => "required|exists:medications,id",
                "schedules" => "required|array",
                "schedules.*" => "required|date_format:H:i",
                "start_datetime" => "date_format:Y-m-d H:i:s"

            ]);

            return ScheduleGenerator::generateSchedule($validatedData);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                'error' => true,
                "message" => $th->getMessage(),
                'errors' => $th->errors()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e,
            ], 500);
        }
    }
}
