<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class HealthVital extends Model
{
    protected $table = 'health_vitals';
    protected $fillable = [
        'patient_id',
        'vital_data'
    ];
    protected $casts = [
        'vital_data' => 'array'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public static function getDefaultUnits()
    {
        return [
            'Blood Pressure' => 'mmHg',
            'Heart Rate' => 'bpm',
            'Glucose' => 'mg/dL',
            'Cholesterol' => 'mg/dL'
        ];
    }

    public static function formatVitalData($name, $value)
    {
        $units = self::getDefaultUnits();
        return [
            'name' => $name,
            'value' => $value,
            'unit' => $units[$name] ?? ''
        ];
    }

    public static function isWithinNormalRange($name, $value)
    {
        $normalRanges = [
            'Blood Pressure' => ['min' => '90/60', 'max' => '120/80'], // Systolic/Diastolic
            'Heart Rate' => ['min' => 60, 'max' => 100], // Normal range: 60-100 bpm
            'Glucose' => ['min' => 70, 'max' => 140], // Normal fasting range: 70-140 mg/dL
            'Cholesterol' => ['min' => 150, 'max' => 240] // Normal range: 150-240 mg/dL
        ];

        if (!isset($normalRanges[$name])) {
            return false;
        }

        if ($name === 'Blood Pressure') {
            if (strpos($value, '/') !== false) {
                [$systolic, $diastolic] = explode('/', $value);
                if (is_numeric($systolic) && is_numeric($diastolic)) {
                    [$minSys, $minDia] = explode('/', $normalRanges[$name]['min']);
                    [$maxSys, $maxDia] = explode('/', $normalRanges[$name]['max']);
                    return ($systolic >= $minSys && $systolic <= $maxSys) &&
                        ($diastolic >= $minDia && $diastolic <= $maxDia);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }


        return $value >= $normalRanges[$name]['min'] && $value <= $normalRanges[$name]['max'];
    }

    public static function getPatientVitalsAndCheckRange($patient_id)
    {
        $healthVital = self::where('patient_id', $patient_id)->first();

        $defaultVitals = [
            'Blood Pressure' => '0/0',
            'Heart Rate' => 0,
            'Glucose' => 0,
            'Cholesterol' => 0
        ];

        if (!$healthVital) {
            $result = [];
            foreach ($defaultVitals as $name => $value) {
                $result[] = self::formatVitalData($name, $value);
            }

            return [
                "error" => false,
                "patient_id" => $patient_id,
                "vitals" => $result
            ];
        }

        $vitalData = is_string($healthVital->vital_data)
            ? json_decode($healthVital->vital_data, true)
            : $healthVital->vital_data;

        Log::info('Retrieved Vital Data:', ['data' => $vitalData]);

        $vitalDataArray = [];
        foreach ($vitalData as $vital) {
            $vitalDataArray[$vital['name']] = $vital['value'];
        }

        $result = [];

        foreach (self::getDefaultUnits() as $name => $unit) {
            $value = $vitalDataArray[$name] ?? $defaultVitals[$name];
            $formattedVital = self::formatVitalData($name, $value);
            $formattedVital["isNormal"] = self::isWithinNormalRange($formattedVital['name'], $formattedVital['value']);
            $result[] = $formattedVital;
        }

        return [
            "error" => false,
            "patient_id" => $patient_id,
            "vitals" => $result
        ];
    }
}
