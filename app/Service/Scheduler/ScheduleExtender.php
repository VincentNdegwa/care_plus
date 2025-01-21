<?php

namespace App\Service\Scheduler;

use App\Models\Schedules\MedicationSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduleExtender extends BaseScheduler
{
    public static function generateSchedule($medicationTracker)
    {

        parent::$app_timezone = config('app.timezone') ?: 'UTC';
        parent::$medication_id = $medicationTracker->medication_id;
        parent::$patient_id = parent::getMedication(parent::$medication_id)->patient_id;

        $last_medication = MedicationSchedule::where('medication_id', parent::$medication_id)->max('dose_time');
        $endDate = Carbon::parse($medicationTracker->end_date);

        $startDate = Carbon::parse($last_medication);

        if ($startDate->gt($endDate)) {
            return [
                "message" => "The medication has already ended"
            ];
        }

        $more_than_month = ($medicationTracker->next_start_month != null) && $startDate->diffInMonths($endDate, false) >= 1;

        $medicationSchedule = [];

        $new_next_start_month = $startDate->copy()->addMonth();
        $new_stop_date = $startDate->copy()->addMonth();

        $scheduleData = [
            'start_date' => $startDate,
            'end_date' => $more_than_month ? $new_next_start_month : $endDate,
            'timezone' => $medicationTracker->timezone,
            'schedules' => json_decode($medicationTracker->schedules),
            'frequency' => $medicationTracker->frequency
        ];

        self::generateSchedules(
            $scheduleData['start_date'],
            $scheduleData['end_date'],
            $scheduleData['schedules'],
            $scheduleData['timezone'],
            $medicationSchedule,
            $scheduleData['frequency']
        );

        $medication_tracker = [
            'medication_id' => $medicationTracker->medication_id,
            'start_date' => $medicationTracker->start_date,
            'end_date' => $medicationTracker->end_date,
            'next_start_month' => $more_than_month ? $new_next_start_month : null,
            'stop_date' => $more_than_month ? $new_stop_date : $endDate,
            'duration' => $medicationTracker->duration,
            'frequency' => $medicationTracker->frequency,
            'timezone' => $medicationTracker->timezone,
            'schedules' => $medicationTracker->schedules,
        ];

        return [
            'medications_schedules' => $medicationSchedule,
            'medication_tracker' => $medication_tracker,
        ];
    }

    private static function generateSchedules($startDate, $endDate, $schedules, $timezone, &$medicationSchedule, $frequency)
    {
        if (!empty($schedules)) {

            self::generateCustomSchedule(
                $startDate,
                $endDate,
                $schedules,
                $medicationSchedule,
                $timezone
            );
        } else {
            Log::info(json_encode($schedules));
            self::generateDefaultSchedule(
                $startDate,
                $endDate,
                $frequency,
                $medicationSchedule,
                $timezone
            );
        }
    }


    private static function generateCustomSchedule($startDate, $stopDay, $schedule, &$medicationSchedule, $timezone)
    {
        $doseTime = $startDate->copy()->setTimezone($timezone);
        $stopDay = $stopDay->copy()->setTimezone($timezone);

        while ($doseTime->lte($stopDay)) {
            foreach ($schedule as $time) {
                [$hour, $minute] = explode(':', $time);

                $appDoseTime = $doseTime->copy()->setTime($hour, $minute)
                    ->setTimezone(parent::$app_timezone);

                $medicationSchedule[] = [
                    'dose_time' => $appDoseTime->format('Y-m-d H:i:s'),
                    'medication_id' => parent::$medication_id,
                    'patient_id' => parent::$patient_id,
                ];
            }

            $doseTime->addDay();
        }
    }




    private static function generateDefaultSchedule($startDate, $stopDay, $frequency, &$medicationSchedule, $timezone)
    {
        $frequencyInterval = parent::getFrequencyInterval($frequency);


        while ($startDate->lte($stopDay)) {
            $medicationSchedule[] = [
                'dose_time' => $startDate->setTimezone($timezone)->format('Y-m-d H:i:s'),
                'medication_id' => parent::$medication_id,
                'patient_id' => parent::$patient_id,
            ];

            parent::updateStartDate($startDate, $frequency, $frequencyInterval);
        }
    }
}
