<?php

namespace App\Service\Scheduler;

use App\Models\Medication;
use App\Models\Schedules\MedicationTracker;
use Carbon\Carbon;
use InvalidArgumentException;

class ScheduleGenerator extends BaseScheduler
{
    public static $scheduledHours = null;

    public static function generateSchedule($custom, $timezone)
    {



        parent::$app_timezone = config('app.timezone') ?: 'UTC';

        $medication_id = $custom['medication_id'];
        $medication = parent::getMedication($medication_id);

        parent::$medication_id = $medication_id;
        parent::$patient_id = $medication->patient_id;

        // Parse the user's start_datetime with the provided timezone, then convert to the app timezone (UTC)
        $startDate = Carbon::parse($custom['start_datetime'], $timezone)->setTimezone(parent::$app_timezone);
        $frequency = $medication->frequency;
        $duration = $medication->duration;

        $endDate = parent::calculateEndDate($startDate->copy(), $duration, parent::$app_timezone);

        $next_start_month = null;
        $nextMonth = $startDate->copy()->addMonth();
        $stopDay = $endDate;
        if ($endDate->gt($nextMonth)) {
            $next_start_month = $nextMonth;
            $stopDay = $nextMonth;
        }
        self::$scheduledHours = isset($custom['schedules']) ? json_encode($custom['schedules']) : null;

        $medicationSchedule = [];


        if (isset($custom['schedules'])) {
            $schedules = $custom['schedules'];
            self::generateCustomSchedule(
                $startDate->copy(),
                $stopDay->copy(),
                $schedules,
                $medicationSchedule,
                $timezone
            );
        } else {
            self::generateDefaultSchedule(
                $startDate->copy(),
                $stopDay->copy(),
                $frequency,
                $medicationSchedule,
                $timezone
            );
        }

        $medication_tracker = [
            'medication_id' => $medication_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_start_month' => $next_start_month,
            'stop_date' => $stopDay,
            'duration' => $duration,
            'frequency' => $frequency,
            'timezone' => $timezone,
            'schedules' => self::$scheduledHours,
        ];

        return [
            'medications_schedules' => $medicationSchedule,
            'medication_tracker' => $medication_tracker,
        ];
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
        $scheduleHours = [];

        while ($startDate->lte($stopDay)) {
            $medicationSchedule[] = [
                'dose_time' => $startDate->setTimezone(parent::$app_timezone)->format('Y-m-d H:i:s'),
                'medication_id' => parent::$medication_id,
                'patient_id' => parent::$patient_id,
            ];
            $hourMinute = $startDate->copy()->setTimezone($timezone)->format('H:i');
            if (!in_array($hourMinute, $scheduleHours)) {
                $scheduleHours[] = $hourMinute;
            }

            parent::updateStartDate($startDate, $frequency, $frequencyInterval);
        }

        self::$scheduledHours = $scheduleHours;
    }
}
