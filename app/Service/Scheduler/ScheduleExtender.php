<?php

namespace App\Service\Scheduler;

use App\Models\Schedules\MedicationSchedule;
use Carbon\Carbon;

class ScheduleExtender extends BaseScheduler
{
    public static function generateSchedule($medicationTracker)
    {

        parent::$app_timezone = config('app.timezone') ?: 'UTC';
        parent::$medication_id = $medicationTracker->medication_id;
        parent::$patient_id = $medicationTracker->patient_id;

        $startDate = Carbon::parse($medicationTracker->next_start_month);
        $endDate = Carbon::parse($medicationTracker->end_date);
        $medicationSchedule = [];

        $new_next_start_month = $startDate->copy()->addMonth();
        $new_stop_date = $startDate->copy()->addMonth();

        $more_than_month = $startDate->diffInMonths($endDate, false) >= 1;
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

        return [
            'medication_id' => $medicationTracker->medication_id,
            'start_date' => $medicationTracker->startDate,
            'end_date' => $medicationTracker->endDate,
            'next_start_month' => $more_than_month ? $new_next_start_month : null,
            'stop_date' => $more_than_month ? $new_stop_date : $endDate,
            'duration' => $medicationTracker->duration,
            'frequency' => $medicationTracker->frequency,
            'timezone' => $medicationTracker->timezone,
            'schedules' => $medicationTracker->schedules,
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
