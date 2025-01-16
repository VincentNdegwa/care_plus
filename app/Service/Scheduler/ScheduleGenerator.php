<?php

namespace App\Service\Scheduler;

use App\Models\Medication;
use Carbon\Carbon;
use InvalidArgumentException;

class ScheduleGenerator
{
    private static $medication_id = null;
    private static $patient_id = null;

    public static function generateSchedule($custom)
    {
        $medication_id = $custom['medication_id'];
        $medication = self::getMedication($medication_id);

        self::$medication_id = $medication_id;
        self::$patient_id = $medication->patient_id;

        $startDate = Carbon::parse($custom['start_datetime']);
        $frequency = $medication->frequency;
        $duration = $medication->duration;

        $endDate = self::calculateEndDate($startDate->copy(), $duration);

        $next_start_month = null;
        $nextMonth = $startDate->copy()->addMonth();
        $stopDay = $endDate;
        if ($endDate->gt($nextMonth)) {
            $next_start_month = $nextMonth;
            $stopDay = $nextMonth;
        }

        $medicationLogs = [];
        $medication_tracker = [
            'medication_id' => $medication_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_start_month' => $next_start_month,
            'stop_date' => $stopDay,
            "duration" => $duration,
            "frequency" => $frequency
        ];

        if (isset($custom['schedules'])) {
            $schedules = $custom['schedules'];
            self::generateCustomSchedule($startDate, $stopDay, $schedules, $medicationLogs);
        } else {
            self::generateDefaultSchedule($startDate, $stopDay, $frequency, $medicationLogs);
        }

        return $medicationLogs;
    }

    private static function generateCustomSchedule($startDate, $stopDay, $schedule, &$medicationLogs)
    {
        $doseTime = $startDate;

        while ($doseTime->lte($stopDay)) {
            foreach ($schedule as $time) {
                [$hour, $minute] = explode(':', $time);
                $doseTime = $doseTime->copy()->setTime($hour, $minute);

                $medicationLogs[] = [
                    'dose_time' => $doseTime->format('Y-m-d H:i:s'),
                    "medication_id" => self::$medication_id,
                    "patient_id" => self::$patient_id,
                ];
            }

            $doseTime->addDay();
        }
    }

    private static function generateDefaultSchedule($startDate, $stopDay, $frequency, &$medicationLogs)
    {
        $frequencyInterval = self::getFrequencyInterval($frequency);

        while ($startDate->lte($stopDay)) {
            $medicationLogs[] = [
                'dose_time' => $startDate->format('Y-m-d H:i:s'),
                "medication_id" => self::$medication_id,
                "patient_id" => self::$patient_id,
            ];

            self::updateStartDate($startDate, $frequency, $frequencyInterval);
        }
    }

    private static function updateStartDate(Carbon &$startDate, $frequency, $frequencyInterval)
    {
        switch ($frequency) {
            case "Once a day":
                $startDate->addDay();
                break;
            case "Twice a day":
                $startDate->addHours(12);
                break;
            default:
                if ($frequencyInterval > 0) {
                    $startDate->addMinutes($frequencyInterval);
                }
                break;
        }
    }

    private static function getMedication($medication_id)
    {
        return Medication::findOrFail(id: $medication_id);
    }

    private static function calculateEndDate(Carbon $startDate, string $duration): Carbon
    {
        preg_match('/(\d+)\s*(day|week|month|year|hour|minute|second)s?/i', $duration, $matches);

        if (!$matches) {
            throw new InvalidArgumentException("Invalid duration format: $duration");
        }

        $value = (int) $matches[1];
        $unit = strtolower($matches[2]);

        return self::addDurationToDate($startDate, $value, $unit);
    }

    private static function addDurationToDate(Carbon $startDate, $value, $unit)
    {
        switch ($unit) {
            case 'day':
                return $startDate->addDays($value);
            case 'week':
                return $startDate->addWeeks($value);
            case 'month':
                return $startDate->addMonths($value);
            case 'year':
                return $startDate->addYears($value);
            case 'hour':
                return $startDate->addHours($value);
            case 'minute':
                return $startDate->addMinutes($value);
            case 'second':
                return $startDate->addSeconds($value);
            default:
                throw new InvalidArgumentException("Unsupported duration unit: $unit");
        }
    }

    private static function getFrequencyInterval($frequency)
    {
        $intervals = [
            "Once a day" => 1440,           // 24 hours = 1440 minutes
            "Twice a day" => 720,           // 12 hours = 720 minutes
            "Three times a day" => 480,     // 8 hours = 480 minutes
            "Four times a day" => 360,      // 6 hours = 360 minutes
            "Once every other day" => 2880, // 48 hours = 2880 minutes
            "Every third day" => 4320,      // 72 hours = 4320 minutes
            "Once a week" => 10080,         // 7 days = 10080 minutes
            "Twice a week" => 4320,         // 3 days = 4320 minutes
            "Three times a week" => 2880,   // 2 days = 2880 minutes
            "Once a month" => 43200,        // 30 days = 43200 minutes
            "Every 6 hours" => 360,         // 6 hours = 360 minutes
            "Every 8 hours" => 480,         // 8 hours = 480 minutes
            "Every 12 hours" => 720,        // 12 hours = 720 minutes
            "Every 24 hours" => 1440,       // 24 hours = 1440 minutes
            "Every hour" => 60,             // 1 hour = 60 minutes
            "Every 2 hours" => 120,         // 2 hours = 120 minutes
            "Every 4 hours" => 240,         // 4 hours = 240 minutes
            "Every 30 minutes" => 30,       // 30 minutes
            "Every 45 minutes" => 45,       // 45 minutes
            "On demand" => 0,               // No fixed schedule
            "As needed" => 0,               // No fixed schedule
            "Until finished" => 0,          // No fixed schedule
            "Until bottle is empty" => 0,   // No fixed schedule
        ];

        // Return the interval for the provided frequency, defaulting to 1440 (Once a day) if not found
        return $intervals[$frequency] ?? 1440;
    }
}
