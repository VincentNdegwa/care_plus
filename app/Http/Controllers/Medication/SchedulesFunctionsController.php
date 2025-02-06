<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Schedules\MedicationSchedule;
use Illuminate\Http\Request;

class SchedulesFunctionsController extends Controller
{
    public function take(Request $request){
        $request->validate([
            "medication_schedule_id" => "required|exists:medication_schedules,id",
            "taken_at" => "required|date"
        ]);

        $medication_schedule = MedicationSchedule::find($request->medication_schedule_id);
        $medication_schedule->taken_at = $request->taken_at;
        $medication_schedule->status = "Taken";
        $medication_schedule->save();
        //remove any snooze record in the table if exist

        return response()->json([
            "error" => false,
            "message" => "Medication taken successfully",
            "data" => $medication_schedule
        ]);
    }

    public function stop(Request $request){
//medication_track = status(stopped, running), stopped_when(datetime)->nullable
//clear all schedules from the one with the processed_at
//update medication to active = 0
    }
    public function snooze(Request $request){
//create medication_snooze table with medication_schedule_id, snooze_time, status(pending, snoozed, dismissed)
//run a job that will check the snooze and trigger the notification
    }

    public function resume(Request $request){
// update medication active = 1
// update medication_tracker status = running
// generate new schedules starting from the last process_at
// more clarification will be required if the medication days will be extended or just proceed with the remaining days

    }
        
}
