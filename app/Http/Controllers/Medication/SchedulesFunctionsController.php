<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\Schedules\MedicationSnooze;
use App\Models\Schedules\MedicationSchedule;
use App\Models\Schedules\MedicationTracker;
use App\Service\Scheduler\ScheduleGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedulesFunctionsController extends Controller
{
    public function take(Request $request)
    {
        $request->validate([
            "medication_schedule_id" => "required|exists:medication_schedules,id",
            "taken_at" => "required|date"
        ]);

        $medication_schedule = MedicationSchedule::find($request->medication_schedule_id);
        $medication_schedule->taken_at = $request->taken_at;
        $medication_schedule->status = "Taken";
        $medication_schedule->save();

        // Remove any snooze records if they exist
        MedicationSnooze::where('medication_schedule_id', $request->medication_schedule_id)
            ->delete();

        return response()->json([
            "error" => false,
            "message" => "Medication taken successfully",
            "data" => $medication_schedule
        ]);
    }

    public function stop(Request $request)
    {
        $request->validate([
            'medication_id' => 'required|exists:medications,id',
        ]);

        try {
            DB::beginTransaction();

            // Update medication tracker
            $tracker = MedicationTracker::where('medication_id', $request->medication_id)
                ->first();
            
            if (!$tracker) {
                return response()->json([
                    'error' => true,
                    'message' => 'No active medication tracker found'
                ], 404);
            }

            $tracker->status = 'stopped';
            $tracker->stopped_when = Carbon::now();
            $tracker->save();

            // Clear future schedules
            MedicationSchedule::where('medication_id', $request->medication_id)
                ->where('processed_at', null)
                ->delete();

            // Update medication status
            Medication::where('id', $request->medication_id)
                ->update(['active' => 0]);

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Medication stopped successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function snooze(Request $request)
    {
        $request->validate([
            'medication_schedule_id' => 'required|exists:medication_schedules,id',
            'snooze_minutes' => 'required|integer|min:1|max:60'
        ]);

        try {
            $schedule = MedicationSchedule::findOrFail($request->medication_schedule_id);
            
            // Calculate snooze time
            $snoozeTime = Carbon::now()->addMinutes($request->snooze_minutes);
            
            // Create or update snooze record
            MedicationSnooze::updateOrCreate(
                ['medication_schedule_id' => $request->medication_schedule_id],
                [
                    'snooze_time' => $snoozeTime,
                    'status' => 'Pending'
                ]
            );

            return response()->json([
                'error' => false,
                'message' => 'Medication snoozed successfully',
                'snooze_time' => $snoozeTime
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function resume(Request $request)
    {
        $request->validate([
            'medication_id' => 'required|exists:medications,id',
            'extend_days' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            $tracker = MedicationTracker::where('medication_id', $request->medication_id)
                ->first();

            if (!$tracker) {
                return response()->json([
                    'error' => true,
                    'message' => 'No medication tracker found'
                ], 404);
            }

            if ($tracker->status !== 'Stopped') {
                return response()->json([
                    'error' => true,
                    'message' => 'Medication is not stopped'
                ], 400);
            }

            $now = Carbon::now();
            $originalEndDate = Carbon::parse($tracker->end_date);
            
            if ($request->input('extend_days', false)) {
                // Calculate minutes difference between stopped time and now
                $stoppedWhen = Carbon::parse($tracker->stopped_when);
                $minutesDifference = $stoppedWhen->diffInMinutes($now);
                
                // Add the difference to the end date
                $newEndDate = $originalEndDate->copy()->addMinutes($minutesDifference);
                
                // Update tracker end date
                $tracker->end_date = $newEndDate;
            }

            // Update medication and tracker status
            $tracker->status = 'Running';
            $tracker->stopped_when = null;
            $tracker->save();

            Medication::where('id', $request->medication_id)
                ->update(['active' => 1]);

            // Generate schedules from now to end date
            $scheduleData = ScheduleGenerator::generateSchedule([
                'medication_id' => $request->medication_id,
                'start_datetime' => $now->format('Y-m-d H:i:s'),
                'end_datetime' => $request->input('extend_days', false) ? 
                    $tracker->end_date : 
                    $originalEndDate->format('Y-m-d H:i:s')
            ], $tracker->timezone);

            // Save new schedules
            foreach ($scheduleData['medications_schedules'] as $schedule) {
                MedicationSchedule::create($schedule);
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Medication resumed successfully',
                'data' => [
                    'original_end_date' => $originalEndDate,
                    'new_end_date' => $request->input('extend_days', false) ? $tracker->end_date : $originalEndDate,
                    'minutes_added' => $request->input('extend_days', false) ? 
                        Carbon::parse($tracker->stopped_when)->diffInMinutes($now) : 0
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
