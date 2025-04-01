<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Schedules\MedicationSchedule;
use App\Models\Schedules\MedicationTracker;
use App\Service\Scheduler\ScheduleExtender;
use App\Service\Scheduler\ScheduleGenerator;
use App\Service\Scheduler\ScheduleSaver;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleMedicationController extends Controller
{
    public function schedule(Request $request, array $rules, string $successMessage)
    {
        try {
            $validatedData = $request->validate($rules);
            $existTrack = MedicationTracker::where('medication_id', $request->input('medication_id'))
                ->where('status', '!=', 'Expired')
                ->exists();
            if ($existTrack) {
                return response()->json(
                    [
                        'error' => true,
                        "message" => "The medication has already been scheduled"
                    ]
                );
            }
            $timezone = "Africa/Nairobi";
            DB::beginTransaction();

            $scheduleData = ScheduleGenerator::generateSchedule($validatedData, $timezone);

            ScheduleSaver::saveSchedule(
                $scheduleData['medications_schedules'],
                $scheduleData['medication_tracker']
            );

            DB::commit();

            Medication::where('id', $request->input('medication_id'))->update([
                'active' => 1
            ]);

            $medicationScheduleData = MedicationSchedule::where("medication_id", $request->input('medication_id'))
                ->with("medication.tracker")
                ->first();

            return response()->json([
                'error' => false,
                'message' => $successMessage,
                'data' => $medicationScheduleData,
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
    public function getMedicationScheduleByDate(Request $request)
    {
        try {
            $rules = [
                "patient_id" => 'required|exists:patients,id',
                "start_date" => 'required|date',
                "end_date" => "required|date"
            ];

            $request->validate($rules);

            $query = MedicationSchedule::query();

            $data = $query->where('dose_time', '>=', $request->input('start_date'))
                ->where('dose_time', '<=', $request->input('end_date'))
                ->with([
                    "medication", 
                ])
                ->get();

            foreach ($data as $schedule) {
                $snooze = $schedule->snoozes()
                    ->where('status', '=', 'Pending')
                    ->first();

                if(isset($snooze)) {
                    $schedule->dose_time = $snooze->snooze_time;
                }

                unset($schedule->snoozes);
            }
            $count = $data->count();

            return response()->json([
                "error" => false,
                "data" => [
                    "count" => $count,
                    "records" => $data,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                "errors" => $th->errors(),
            ]);
        } catch (Exception $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                "errors" => $th,
            ]);
        }
    }

    public function getTodaysPatientMedicationSchedule($patient_id, Request $request)
    {
        $patient = Patient::find($patient_id);
        if (!$patient) {
            return response()->json(['error' => true, 'message' => 'Patient not found'], 404);
        }

        $date = $request->query('today_date');
        $parsedDate = $date ? Carbon::parse($date) : Carbon::now();

        $schedules = $patient->todaySchedules($parsedDate);

        return response()->json([
            "error" => false,
            "schedules" => $schedules
        ]);
    }

    public function generateScheduleTimes(Request $request)
    {
        $validatedData = $request->validate([
            'medication_id' => 'required|exists:medications,id',
            'start_datetime' => 'date_format:Y-m-d H:i:s',
            'timezone' => 'nullable|string'
        ]);

        $validatedData['timezone'] = $request->input('timezone', 'Africa/Nairobi');


        return ScheduleGenerator::getDefaultDoseTimes($validatedData);
    }
}
