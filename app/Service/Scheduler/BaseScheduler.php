<?php

namespace App\Service\Scheduler;

use App\Models\Medication;
use Carbon\Carbon;
use InvalidArgumentException;

class BaseScheduler
{
    protected static $medication_id = null;
    protected static $patient_id = null;
    protected static $app_timezone = null;

    protected static function getMedication($medication_id)
    {
        return Medication::findOrFail($medication_id);
    }

    protected static function calculateEndDate(Carbon $startDate, string $duration, $timezone): Carbon
    {
        preg_match('/(\d+)\s*(day|week|month|year|hour|minute|second)s?/i', $duration, $matches);

        if (!$matches) {
            throw new InvalidArgumentException("Invalid duration format: $duration");
        }

        $value = (int) $matches[1];
        $unit = strtolower($matches[2]);

        return self::addDurationToDate($startDate, $value, $unit, $timezone);
    }

    protected static function addDurationToDate(Carbon $startDate, $value, $unit, $timezone)
    {
        switch ($unit) {
            case 'day':
                return $startDate->addDays($value)->setTimezone($timezone);
            case 'week':
                return $startDate->addWeeks($value)->setTimezone($timezone);
            case 'month':
                return $startDate->addMonths($value)->setTimezone($timezone);
            case 'year':
                return $startDate->addYears($value)->setTimezone($timezone);
            case 'hour':
                return $startDate->addHours($value)->setTimezone($timezone);
            case 'minute':
                return $startDate->addMinutes($value)->setTimezone($timezone);
            case 'second':
                return $startDate->addSeconds($value)->setTimezone($timezone);
            default:
                throw new InvalidArgumentException("Unsupported duration unit: $unit");
        }
    }

    protected static function getFrequencyInterval($frequency)
    {
        $intervals = [
            'Once a day' => 1440,           // 24 hours = 1440 minutes
            'Twice a day' => 720,           // 12 hours = 720 minutes
            'Three times a day' => 480,     // 8 hours = 480 minutes
            'Four times a day' => 360,      // 6 hours = 360 minutes
            'Once every other day' => 2880, // 48 hours = 2880 minutes
            'Every third day' => 4320,      // 72 hours = 4320 minutes
            'Once a week' => 10080,         // 7 days = 10080 minutes
            'Twice a week' => 4320,         // 3 days = 4320 minutes
            'Three times a week' => 2880,   // 2 days = 2880 minutes
            'Once a month' => 43200,        // 30 days = 43200 minutes
            'Every 6 hours' => 360,         // 6 hours = 360 minutes
            'Every 8 hours' => 480,         // 8 hours = 480 minutes
            'Every 12 hours' => 720,        // 12 hours = 720 minutes
            'Every 24 hours' => 1440,       // 24 hours = 1440 minutes
            'Every hour' => 60,             // 1 hour = 60 minutes
            'Every 2 hours' => 120,         // 2 hours = 120 minutes
            'Every 4 hours' => 240,         // 4 hours = 240 minutes
            'Every 30 minutes' => 30,       // 30 minutes
            'Every 45 minutes' => 45,       // 45 minutes
            'On demand' => 0,               // No fixed schedule
            'As needed' => 0,               // No fixed schedule
            'Until finished' => 0,          // No fixed schedule
            'Until bottle is empty' => 0,   // No fixed schedule
        ];
        return $intervals[$frequency] ?? 1440;
    }

    protected static function updateStartDate(Carbon &$startDate, $frequency, $frequencyInterval)
    {
        switch ($frequency) {
            case 'Once a day':
                $startDate->addDay();
                break;
            case 'Twice a day':
                $startDate->addHours(12);
                break;
            default:
                if ($frequencyInterval > 0) {
                    $startDate->addMinutes($frequencyInterval);
                }
                break;
        }
    }
}
