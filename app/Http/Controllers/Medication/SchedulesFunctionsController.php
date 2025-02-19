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

    private function getNextScheduleTime($lastDoseTime, $schedules, $timezone)
    {
        // Convert schedules from user timezone to UTC
        $utcSchedules = collect(json_decode($schedules))->map(function ($time) use ($timezone) {
            // Create a datetime with today's date and the schedule time in user's timezone
            $scheduleDateTime = Carbon::now($timezone)
                ->setTimeFromTimeString($time)
                ->setTimezone('UTC');

            return $scheduleDateTime->format('H:i');
        })->sort();

        // Convert last dose time to user's timezone for comparison
        $lastDoseInUserTz = Carbon::parse($lastDoseTime);
        $nowInUserTz = Carbon::now()->setTimezone($timezone);

        // Find the next schedule time after the last dose
        foreach ($utcSchedules as $schedule) {
            list($hour, $minute) = explode(':', $schedule);

            // Create schedule datetime in UTC
            $scheduleDateTime = Carbon::now('UTC')
                ->setTime($hour, $minute);

            // If schedule is earlier than now, move to next day
            if ($scheduleDateTime->isPast()) {
                $scheduleDateTime->addDay();
            }

            // If this schedule is after the last dose time, use it
            if ($scheduleDateTime->isAfter($lastDoseInUserTz)) {
                return $scheduleDateTime;
            }
        }

        // If no schedule found after last dose, use the first schedule of next day
        $firstSchedule = $utcSchedules->first();
        list($hour, $minute) = explode(':', $firstSchedule);
        return Carbon::now('UTC')
            ->addDay()
            ->setTime($hour, $minute);
    }

    public function resume(Request $request)
    {
        $request->validate([
            'medication_id' => 'required|exists:medications,id',
            'extend_days' => 'boolean',
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

            // Get the last dose time
            $lastDose = MedicationSchedule::where('medication_id', $request->medication_id)
                ->where('status', 'Taken')
                ->orderBy('scheduled_at', 'desc')
                ->first();

            $now = Carbon::now();
            $originalEndDate = Carbon::parse($tracker->end_date);

            // Determine restart time based on last dose and schedules
            $restart_from = $lastDose
                ? $this->getNextScheduleTime($lastDose->scheduled_at, $tracker->schedules, $tracker->timezone)
                : $now;

            if ($request->input('extend_days', false)) {
                $stoppedWhen = Carbon::parse($tracker->stopped_when);
                $minutesDifference = $stoppedWhen->diffInMinutes($restart_from);
                $newEndDate = $originalEndDate->copy()->addMinutes($minutesDifference);

                if ($newEndDate->isPast()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Cannot resume medication. End date is in the past. Create a new schedule instead.'
                    ], 400);
                }

                $tracker->end_date = $newEndDate;
            }

            $restart_from = Carbon::parse($restart_from, "UTC")->setTimezone($tracker->timezone);
            Medication::where('id', $request->medication_id)
                ->update(['active' => 1]);

            // Generate schedules from restart time to end date
            $scheduleData = ScheduleGenerator::generateSchedule([
                'medication_id' => $request->medication_id,
                'start_datetime' => $restart_from->format('Y-m-d H:i:s'),
                'end_datetime' => $request->input('extend_days', false) ?
                    $tracker->end_date :
                    $originalEndDate->format('Y-m-d H:i:s'),
                "schedule" => json_decode($tracker->schedules)
            ], $tracker->timezone);

            // Save new schedules
            foreach ($scheduleData['medications_schedules'] as $schedule) {
                MedicationSchedule::create($schedule);
            }

            $tracker->status = 'Running';
            $tracker->stopped_when = null;
            $tracker->save();

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Medication resumed successfully',
                'data' => [
                    'original_end_date' => $originalEndDate,
                    'new_end_date' => $request->input('extend_days', false) ? $tracker->end_date : $originalEndDate,
                    'restart_from' => $restart_from,
                    'minutes_added' => $request->input('extend_days', false) ?
                        Carbon::parse($tracker->stopped_when)->diffInMinutes($restart_from) : 0
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

    public function takeNow(Request $request)
    {
        $request->validate([
            "medication_id" => "required|exists:medications,id",
        ]);

        $now = Carbon::now();
        $pastFiveMinutes = $now->copy()->subMinutes(5);
        $futureFiveMinutes = $now->copy()->addMinutes(5);

        $schedule = MedicationSchedule::where('medication_id', $request->medication_id)
            ->whereBetween('dose_time', [$pastFiveMinutes, $futureFiveMinutes])
            ->where('status', 'Pending')
            ->orderBy('dose_time', 'asc')
            ->first();

        if (!$schedule) {
            return response()->json([
                "error" => true,
                'message' => 'No pending medication schedule found within 5 minutes of current time. Wait for Notification'
            ], 404);
        }

        $schedule->status = 'Taken';
        $schedule->processed_at = Carbon::now();
        $schedule->taken_at = Carbon::now();
        $schedule->save();

        return response()->json([
            'error' => false,
            'message' => 'Medication taken successfully',
            'data' => $schedule
        ]);
    }
}
