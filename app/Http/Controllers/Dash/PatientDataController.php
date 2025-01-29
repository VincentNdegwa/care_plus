<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Models\CaregiverRelation;
use App\Models\Diagnosis;
use App\Models\DoctorRelation;
use App\Models\HealthVital;
use App\Models\Medication;
use App\Models\SideEffect;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

class PatientDataController extends Controller
{
    /**
     * Get counts of medications, caregivers, side effects, and diagnoses for a specific patient.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $patientId)
    {

        // Validate the patient_id
        if (!$patientId) {
            return response()->json(['error' => 'Patient ID is required'], 400);
        }

        // Get current year and last year
        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;

        // Get counts for current year
        $medicationCountCurrentYear = Medication::where('patient_id', $patientId)
            ->whereYear('created_at', $currentYear)
            ->count();
        $caregiverCountCurrentYear = CaregiverRelation::where('patient_id', $patientId)
            ->whereYear('created_at', $currentYear)
            ->count();
        $doctorCountCurrentYear = DoctorRelation::where("patient_id", $patientId)
            ->whereYear('created_at', $currentYear)
            ->count();
        $sideEffectCountCurrentYear = SideEffect::where('patient_id', $patientId)
            ->whereYear('created_at', $currentYear)
            ->count();
        $diagnosisCountCurrentYear = Diagnosis::where('patient_id', $patientId)
            ->whereYear('created_at', $currentYear)
            ->count();

        // Get counts for last year
        $medicationCountLastYear = Medication::where('patient_id', $patientId)
            ->whereYear('created_at', $lastYear)
            ->count();
        $caregiverCountLastYear = CaregiverRelation::where('patient_id', $patientId)
            ->whereYear('created_at', $lastYear)
            ->count();
        $doctorCountLastYear = DoctorRelation::where("patient_id", $patientId)
            ->whereYear('created_at', $lastYear)
            ->count();
        $sideEffectCountLastYear = SideEffect::where('patient_id', $patientId)
            ->whereYear('created_at', $lastYear)
            ->count();
        $diagnosisCountLastYear = Diagnosis::where('patient_id', $patientId)
            ->whereYear('created_at', $lastYear)
            ->count();

        // Calculate percentage change
        $medicationChange = $this->calculatePercentageChange($medicationCountLastYear, $medicationCountCurrentYear);
        $caregiverChange = $this->calculatePercentageChange(($caregiverCountLastYear + $doctorCountLastYear), ($caregiverCountCurrentYear + $doctorCountCurrentYear));
        $sideEffectChange = $this->calculatePercentageChange($sideEffectCountLastYear, $sideEffectCountCurrentYear);
        $diagnosisChange = $this->calculatePercentageChange($diagnosisCountLastYear, $diagnosisCountCurrentYear);

        $heatlthVitals = HealthVital::getPatientVitalsAndCheckRange($patientId);

        // Return the counts and changes as a JSON response in the specified format
        return response()->json([
            "patient_stats" => [

                'medication' => [
                    'current' => $medicationCountCurrentYear,
                    'last' => $medicationCountLastYear,
                    'change' => $medicationChange,
                    "label" => "This year"
                ],
                'caregiver' => [
                    'current' => $caregiverCountCurrentYear,
                    'last' => $caregiverCountLastYear,
                    'change' => $caregiverChange,
                    "label" => "This year"
                ],
                'side_effect' => [
                    'current' => $sideEffectCountCurrentYear,
                    'last' => $sideEffectCountLastYear,
                    'change' => $sideEffectChange,
                    "label" => "This year"
                ],
                'diagnosis' => [
                    'current' => $diagnosisCountCurrentYear,
                    'last' => $diagnosisCountLastYear,
                    'change' => $diagnosisChange,
                    "label" => "This year"
                ],
            ],
            "health_vitals" => $heatlthVitals['vitals']
        ]);
    }

    /**
     * Calculate the percentage change between two counts.
     *
     * @param  int  $lastYearCount
     * @param  int  $currentYearCount
     * @return string
     */
    private function calculatePercentageChange($lastYearCount, $currentYearCount)
    {
        if ($lastYearCount == 0) {
            return $currentYearCount > 0 ? '+100%' : '0%';
        }

        $change = (($currentYearCount - $lastYearCount) / $lastYearCount) * 100;
        return ($change > 0 ? '+' : '-') . round($change, 2) . '%';
    }
}
