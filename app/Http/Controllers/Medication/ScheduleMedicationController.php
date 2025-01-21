<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Schedules\MedicationTracker;
use App\Service\Scheduler\ScheduleExtender;
use App\Service\Scheduler\ScheduleGenerator;
use App\Service\Scheduler\ScheduleSaver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleMedicationController extends Controller
{
    public function schedule(Request $request, array $rules, string $successMessage)
    {
        try {
            $validatedData = $request->validate($rules);

            $timezone = "Africa/Nairobi";
            DB::beginTransaction();

            $scheduleData = ScheduleGenerator::generateSchedule($validatedData, $timezone);

            ScheduleSaver::saveSchedule(
                $scheduleData['medications_schedules'],
                $scheduleData['medication_tracker']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => $scheduleData,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e,
            ], 500);
        }
    }
    public function extend($medication_tracker_id)
    {
        $medication_track = MedicationTracker::find($medication_tracker_id);

        $scheduleData = ScheduleExtender::generateSchedule($medication_track);
        // ScheduleSaver::saveSchedule(
        //     $scheduleData['medications_schedules'],
        //     $scheduleData['medication_tracker']
        // );

        return response()->json([
            'success' => true,
            'data' => $scheduleData,
        ]);
    }

    public function scheduleDefault(Request $request)
    {
        return $this->schedule(
            $request,
            [
                'medication_id' => 'required|exists:medications,id',
                'start_datetime' => 'date_format:Y-m-d H:i:s',
            ],
            'Medication schedule created successfully.'
        );
    }

    public function scheduleCustom(Request $request)
    {
        return $this->schedule(
            $request,
            [
                'medication_id' => 'required|exists:medications,id',
                'schedules' => 'required|array',
                'schedules.*' => 'required|date_format:H:i',
                'start_datetime' => 'date_format:Y-m-d H:i:s',
            ],
            'Custom medication schedule created successfully.'
        );
    }
}
