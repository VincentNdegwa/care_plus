<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ScheduleMedicationController extends Controller
{
    public function scheduleDefault(Request $request)
    {
        try {
            $validateData = $request->validate([
                "medication_id" => "required|exists:medications,id"
            ]);

            $medication = Medication::find($validateData['medication_id']);
            return $this->generateMedicationScheduleWithoutStock($medication->frequency, $medication->duration, $medication->prescribed_date);
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
                "start_datetime" => "nullable|date_format:Y-m-d H:i:s"

            ]);
            $medication = Medication::find($validatedData['medication_id']);
            $startDate = Carbon::now();
            if (isset($validatedData['start_datetime'])) {
                $startDate = Carbon::parse($validatedData['start_datetime']);
            }
            return $this->generateMedicationScheduleCustom($validatedData['schedules'], $medication->duration, $startDate);
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

    private function generateMedicationScheduleCustom($schedules, $duration, $startDate)
    {
        $endDate = $this->calculateEndDate($startDate->copy(), $duration);
        $doseSchedules = [];

        if ($startDate->lte($endDate)) {
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                foreach ($schedules as $time) {
                    [$hour, $minute] = explode(':', $time);
                    $doseTime = $date->copy()->setTime($hour, $minute);

                    if ($doseTime->lte($endDate)) {
                        $doseSchedules[] = [
                            'dose_time' => $doseTime->format('Y-m-d H:i:s'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        return $doseSchedules;
    }


    function generateMedicationScheduleWithoutStock($frequency, $duration, $startDate)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = $this->calculateEndDate($startDate->copy(), $duration);

        $frequencyIntervals = $this->getFrequencyInterval($frequency);

        $medicationLogs = [];
        $currentDoseTime = $startDate;

        while ($currentDoseTime->lte($endDate)) {

            $medicationLogs[] = [
                'dose_time' => $currentDoseTime->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($frequency === "Once a day") {
                $currentDoseTime->addDays(1);
            } elseif ($frequency === "Twice a day") {
                $currentDoseTime->addHours(12);
            } else {
                $currentDoseTime->addMinutes($frequencyIntervals);
            }
        }

        return response()->json($medicationLogs);
    }
    function getFrequencyInterval($frequency)
    {
        switch ($frequency) {
            case "Once a day":
                return 24 * 60; // 24 hours
            case "Twice a day":
                return 12 * 60; // 12 hours
            case "Three times a day":
                return 8 * 60; // 8 hours
            case "Four times a day":
                return 6 * 60; // 6 hours
            case "Once every other day":
                return 48 * 60; // 48 hours
            case "Every third day":
                return 72 * 60; // 72 hours
            case "Once a week":
                return 7 * 24 * 60; // 7 days
            case "Twice a week":
                return 3 * 24 * 60; // 3 days
            case "Three times a week":
                return 2 * 24 * 60; // 2 days
            case "Once a month":
                return 30 * 24 * 60; // 30 days
            case "Every 6 hours":
                return 6 * 60; // 6 hours
            case "Every 8 hours":
                return 8 * 60; // 8 hours
            case "Every 12 hours":
                return 12 * 60; // 12 hours
            case "Every 24 hours":
                return 24 * 60; // 24 hours
            case "Every hour":
                return 60; // 1 hour
            case "Every 2 hours":
                return 2 * 60; // 2 hours
            case "Every 4 hours":
                return 4 * 60; // 4 hours
            case "Every 6 hours":
                return 6 * 60; // 6 hours
            case "Every 30 minutes":
                return 30; // 30 minutes
            case "Every 45 minutes":
                return 45; // 45 minutes
            case "On demand":
                return 0; // No fixed schedule
            case "As needed":
                return 0; // No fixed schedule
            case "Until finished":
                return 0; // No fixed schedule
            case "Until bottle is empty":
                return 0; // No fixed schedule
            default:
                return 24 * 60; // Default to "Once a day"
        }
    }
    function calculateEndDate(Carbon $startDate, string $duration): Carbon
    {
        preg_match('/(\d+)\s*(day|week|month|year|hour|minute|second)s?/i', $duration, $matches);

        if (!$matches) {
            throw new InvalidArgumentException("Invalid duration format: $duration");
        }

        $value = (int) $matches[1];
        $unit = strtolower($matches[2]);
        $totalMinutes = 0;

        switch ($unit) {
            case 'day':
                $totalMinutes = $value * 24 * 60;
                break;
            case 'week':
                $totalMinutes = $value * 7 * 24 * 60;
                break;
            case 'month':
                $totalMinutes = $value * 30 * 24 * 60;
                break;
            case 'year':
                $totalMinutes = $value * 365 * 24 * 60;
                break;
            case 'hour':
                $totalMinutes = $value * 60;
                break;
            case 'minute':
                $totalMinutes = $value;
                break;
            case 'second':
                $totalMinutes = $value / 60;
                break;
            default:
                throw new InvalidArgumentException("Unsupported duration unit: $unit");
        }

        // Add the total minutes to the start date
        return $startDate->addMinutes($totalMinutes);
    }
}
