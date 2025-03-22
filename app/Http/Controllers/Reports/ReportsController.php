<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\Schedules\MedicationSchedule;
use App\Models\CaregiverRelation;
use App\Models\DoctorRelation;
use App\Models\Patient;
use App\Models\Schedules\MedicationTracker;
use App\Models\SideEffect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    public function medicalAdhearanceReport(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'patient_id' => "required|integer|exists:patients,id",
            'medication_id' => "nullable|integer|exists:medications,id",
        ]);

        $fromDate = $request->input('from_date') ? $request->input('from_date') : now()->startOfMonth();
        $toDate = $request->input('to_date') ? $request->input('to_date') : now();

        $scheduledQuery = MedicationSchedule::where('patient_id', $request->patient_id)
            ->whereBetween('dose_time', [$fromDate, $toDate]);

        if ($request->filled('medication_id')) {
            $scheduledQuery->where('medication_id', $request->medication_id);
        }

        $totalScheduled = $scheduledQuery->where('status', '!=', 'Pending')->count();

        $takenQuery = MedicationSchedule::where('patient_id', $request->patient_id)
            ->whereBetween('dose_time', [$fromDate, $toDate]);

        if ($request->filled('medication_id')) {
            $takenQuery->where('medication_id', $request->medication_id);
        }

        $totalTaken = $takenQuery->where('status', 'Taken')->count();

        $adherencePercentage = $totalScheduled > 0 ? ($totalTaken / $totalScheduled) * 100 : 0;

        return response()->json(
            [
                'error' => false,
                'data' => [
                    'total_scheduled' => $totalScheduled,
                    'total_taken' => $totalTaken,
                    'adherence_percentage' => round($adherencePercentage, 2),
                    'from_date' => $fromDate,
                    'to_date' => $toDate
                ]
            ]
        );
    }

    public function medicationAdherenceByMedication(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'patient_id' => "required|integer|exists:patients,id",
        ]);

        $fromDate = $request->input('from_date') ? $request->input('from_date') : now()->startOfMonth();
        $toDate = $request->input('to_date') ? $request->input('to_date') : now();

        $medications = MedicationSchedule::where('patient_id', $request->patient_id)
            ->whereBetween('dose_time', [$fromDate, $toDate])
            ->select('medication_id')
            ->distinct()
            ->get();

        $adherenceData = [];

        foreach ($medications as $medication) {
            $medicationId = $medication->medication_id;

            $totalScheduled = MedicationSchedule::where('patient_id', $request->patient_id)
                ->where('medication_id', $medicationId)
                ->whereBetween('dose_time', [$fromDate, $toDate])
                ->where('status', '!=', 'Pending')
                ->count();

            $totalTaken = MedicationSchedule::where('patient_id', $request->patient_id)
                ->where('medication_id', $medicationId)
                ->whereBetween('dose_time', [$fromDate, $toDate])
                ->where('status', 'Taken')
                ->count();

            $adherencePercentage = $totalScheduled > 0 ? ($totalTaken / $totalScheduled) * 100 : 0;

            $adherenceData[] = [
                'medication_id' => $medicationId,
                'medication_name' => Medication::find($medicationId)->medication_name,
                'total_scheduled' => $totalScheduled,
                'total_taken' => $totalTaken,
                'adherence_percentage' => round($adherencePercentage, 2),
            ];
        }

        return response()->json([
            'error' => false,
            'data' => $adherenceData,
            'message' => 'Medication adherence report by medication generated successfully.'
        ]);
    }

    public function mostMissedMedications(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'patient_id' => "required|integer|exists:patients,id",
        ]);

        $fromDate = $request->input('from_date') ? $request->input('from_date') : now()->startOfMonth();
        $toDate = $request->input('to_date') ? $request->input('to_date') : now();

        $medications = MedicationSchedule::where('patient_id', $request->patient_id)
            ->whereBetween('dose_time', [$fromDate, $toDate])
            ->select('medication_id')
            ->distinct()
            ->get();

        $missedData = [];

        foreach ($medications as $medication) {
            $medicationId = $medication->medication_id;

            $missedCount = MedicationSchedule::where('patient_id', $request->patient_id)
                ->where('medication_id', $medicationId)
                ->whereBetween('dose_time', [$fromDate, $toDate])
                ->where('status', 'Missed')
                ->count();

            if ($missedCount > 0) {
                $missedData[] = [
                    'medication_id' => $medicationId,
                    'medication_name' => Medication::find($medicationId)->medication_name,
                    'missed_count' => $missedCount,
                ];
            }
        }

        usort($missedData, function ($a, $b) {
            return $b['missed_count'] <=> $a['missed_count'];
        });

        return response()->json([
            'error' => false,
            'data' => $missedData,
            'message' => 'Most missed medications report generated successfully.'
        ]);
    }

    public function adherencePerPatient(Request $request)
    {
        if (!in_array($request->user()->role, ['Doctor', 'Caregiver'])) {
            return response()->json([
                'error' => true,
                'message' => 'Permission denied.'
            ], 403);
        }

        $patientIds = [];
        if ($request->user()->role === 'Doctor') {
            $doctorId = $request->user()->doctor->id;
            $patientIds = DoctorRelation::where('doctor_id', $doctorId)->pluck('patient_id')->toArray();
        } elseif ($request->user()->role === 'Caregiver') {
            $caregiverId = $request->user()->caregiver->id;
            $patientIds = CaregiverRelation::where('caregiver_id', $caregiverId)->pluck('patient_id')->toArray();
        }

        $adherenceData = [];

        foreach ($patientIds as $patientId) {
            $totalScheduled = MedicationSchedule::where('patient_id', $patientId)
                ->where('status', '!=', 'Pending')
                ->count();

            $totalTaken = MedicationSchedule::where('patient_id', $patientId)
                ->where('status', 'Taken')
                ->count();

            $adherencePercentage = $totalScheduled > 0 ? ($totalTaken / $totalScheduled) * 100 : 0;

            $patient = Patient::with('user')->find($patientId);
            $patientName = $patient ? $patient->user->name : null;

            $adherenceData[] = [
                'patient_id' => $patientId,
                'patient_name' => $patientName,
                'total_scheduled' => $totalScheduled,
                'total_taken' => $totalTaken,
                'adherence_percentage' => round($adherencePercentage, 2),
            ];
        }

        return response()->json([
            'error' => false,
            'data' => $adherenceData,
            'message' => 'Adherence report per patient generated successfully.'
        ]);
    }

    public function topAdheringPatients(Request $request)
    {
        if (!in_array($request->user()->role, ['Doctor', 'Caregiver'])) {
            return response()->json([
                'error' => true,
                'message' => 'Permission denied.'
            ], 403);
        }

        return $this->getAdheringPatients($request);
    }

    public function bottomAdheringPatients(Request $request)
    {
        if (!in_array($request->user()->role, ['Doctor', 'Caregiver'])) {
            return response()->json([
                'error' => true,
                'message' => 'Permission denied.'
            ], 403);
        }

        return $this->getAdheringPatients($request);
    }

    private function getAdheringPatients(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $fromDate = $request->input('from_date') ? $request->input('from_date') : now()->startOfMonth();
        $toDate = $request->input('to_date') ? $request->input('to_date') : now();

        $patientIds = [];
        if ($request->user()->role === 'Doctor') {
            $doctorId = $request->user()->doctor->id;
            $patientIds = DoctorRelation::where('doctor_id', $doctorId)->pluck('patient_id')->toArray();
        } elseif ($request->user()->role === 'Caregiver') {
            $caregiverId = $request->user()->caregiver->id;
            $patientIds = CaregiverRelation::where('caregiver_id', $caregiverId)->pluck('patient_id')->toArray();
        }

        $adherenceData = [];

        foreach ($patientIds as $patientId) {
            $totalScheduled = MedicationSchedule::where('patient_id', $patientId)
                ->where('status', '!=', 'Pending')
                ->whereBetween('dose_time', [$fromDate, $toDate])
                ->count();

            $totalTaken = MedicationSchedule::where('patient_id', $patientId)
                ->where('status', 'Taken')
                ->whereBetween('dose_time', [$fromDate, $toDate])
                ->count();

            $adherencePercentage = $totalScheduled > 0 ? ($totalTaken / $totalScheduled) * 100 : 0;

            $adherenceData[] = [
                'patient' => Patient::with('user')->find($patientId),
                'adherence_percentage' => round($adherencePercentage, 2),
            ];
        }

        usort($adherenceData, function ($a, $b) {
            return $b['adherence_percentage'] <=> $a['adherence_percentage'];
        });

        $topAdheringPatients = array_slice($adherenceData, 0, 5);
        $bottomAdheringPatients = array_slice($adherenceData, -10);

        return response()->json([
            'error' => false,
            'top_adhering_patients' => $topAdheringPatients,
            'bottom_adhering_patients' => $bottomAdheringPatients,
            'message' => 'Adhering patients report generated successfully.'
        ]);
    }

    public function fetchSideEffects(Request $request)
    {
        if (!in_array($request->user()->role, ['Doctor', 'Caregiver'])) {
            return response()->json([
                'error' => true,
                'message' => 'Permission denied.'
            ], 403);
        }

        $patientIds = [];
        if ($request->user()->role === 'Doctor') {
            $doctorId = $request->user()->doctor->id;
            $patientIds = DoctorRelation::where('doctor_id', $doctorId)->pluck('patient_id')->toArray();
        } elseif ($request->user()->role === 'Caregiver') {
            $caregiverId = $request->user()->caregiver->id;
            $patientIds = CaregiverRelation::where('caregiver_id', $caregiverId)->pluck('patient_id')->toArray();
        }

        $sideEffectsData = [];

        foreach ($patientIds as $patientId) {
            $sideEffects = SideEffect::where('patient_id', $patientId)->get();

            foreach ($sideEffects as $sideEffect) {
                $sideEffectsData[] = [
                    'patient_id' => $patientId,
                    'patient_name' => Patient::with('user')->find($patientId)->user->name,
                    'side_effect' => $sideEffect->side_effect,
                    'severity' => $sideEffect->severity,
                    'datetime' => $sideEffect->datetime,
                    'duration' => $sideEffect->duration,
                    'notes' => $sideEffect->notes,
                ];
            }
        }

        return response()->json([
            'error' => false,
            'data' => $sideEffectsData,
            'message' => 'Side effects fetched successfully.'
        ]);
    }

    public function medicationVsSideEffectCounts(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $patientId = $request->input('patient_id');
        $fromDate = $request->input('from_date') ? $request->input('from_date') : now()->startOfMonth();
        $toDate = $request->input('to_date') ? $request->input('to_date') : now();

        $medicationData = [];

        $medications = Medication::where('patient_id', $patientId)->get();

        foreach ($medications as $medication) {
            $medicationId = $medication->id;

            $sideEffectCount = SideEffect::where('patient_id', $patientId)
                ->where('medication_id', $medicationId);

            if ($fromDate) {
                $sideEffectCount->where('datetime', '>=', $fromDate);
            }

            if ($toDate) {
                $sideEffectCount->where('datetime', '<=', $toDate);
            }

            $sideEffectCount = $sideEffectCount->count();

            $medicationData[] = [
                'medication_id' => $medicationId,
                'medication_name' => $medication->medication_name,
                'side_effect_count' => $sideEffectCount,
            ];
        }

        return response()->json([
            'error' => false,
            'data' => $medicationData,
            'message' => 'Medication vs Side Effect counts fetched successfully.'
        ]);
    }

    public function topSideEffects(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $patientId = $request->input('patient_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $sideEffectsQuery = SideEffect::where('patient_id', $patientId);

        if ($fromDate) {
            $sideEffectsQuery->where('datetime', '>=', $fromDate);
        }

        if ($toDate) {
            $sideEffectsQuery->where('datetime', '<=', $toDate);
        }

        $sideEffects = $sideEffectsQuery->get();

        $severityOrder = [
            'Severe' => 1,
            'Moderate' => 2,
            'Mild' => 3,
        ];

        $sideEffectsData = $sideEffects->sortBy(function ($sideEffect) use ($severityOrder) {
            return $severityOrder[$sideEffect->severity];
        });

        $sideEffectsData = $sideEffectsData->values();

        $responseData = [];

        foreach ($sideEffectsData as $sideEffect) {
            $responseData[] = [
                'patient_id' => $patientId,
                'patient_name' => Patient::with('user')->find($patientId)->user->name,
                'side_effect' => $sideEffect->side_effect,
                'severity' => $sideEffect->severity,
                'datetime' => $sideEffect->datetime,
                'duration' => $sideEffect->duration,
                'notes' => $sideEffect->notes,
            ];
        }

        return response()->json([
            'error' => false,
            'data' => $responseData,
            'message' => 'Top side effects fetched successfully.'
        ]);
    }

    public function medicationProgress(Request $request)
    {
        $medication_id = $request->query("medication_id");
        $tracker = MedicationTracker::where("medication_id", $medication_id)->first();
        $taken = MedicationSchedule::where('medication_id', $medication_id)->where('status', 'Taken')->count();

        if ($tracker == null) {
            return [
                'progress' => 0,
                'total_schedules' => 0,
                'completed_schedules' => 0,
                'taken_schedules' => $taken
            ];
        }
        $schedules = json_decode($tracker->schedules, true);
        if (empty($schedules)) {
            return [
                'progress' => 0,
                'total_schedules' => 0,
                'completed_schedules' => 0,
                'taken_schedules' => $taken
            ];
        }

        // Count daily doses based on schedule times
        $doses_per_day = count($schedules);


        $timezone = config('app.timezone');
        $now = now()->timezone($timezone);
        $start_date = \Carbon\Carbon::parse($tracker->start_date);
        $end_date = \Carbon\Carbon::parse($tracker->end_date);
        // Calculate total expected doses
        $total_expected_doses = $start_date->diffInDays($end_date) * $doses_per_day;

        // Get completed schedules count
        $completed_schedules = MedicationSchedule::where('medication_id', $medication_id)
            ->whereIn("status", ["Taken", "Missed"])
            ->count();

        if ($tracker->status === 'Completed' || $now->greaterThan($end_date)) {
            return [
                'progress' => 100,
                'total_schedules' => (int)$total_expected_doses,
                'completed_schedules' => (int)$completed_schedules,
                'taken_schedules' => $taken

            ];
        }
        // Handle frequencies with no fixed schedule
        if (in_array($tracker->frequency, ['On demand', 'As needed', 'Until finished', 'Until bottle is empty'])) {

            return [
                'progress' => 0,
                'total_schedules' => (int)$total_expected_doses,
                'completed_schedules' => (int)$completed_schedules,
                'taken_schedules' => $taken
            ];
        }

        // If start date is in future, return 0 progress
        if ($now->lessThan($start_date)) {
            return [
                'progress' => 0,
                'total_schedules' => (int)$total_expected_doses,
                'completed_schedules' => (int)$completed_schedules,
                'taken_schedules' => $taken
            ];
        }


        // Calculate progress percentage
        $progress = $total_expected_doses > 0 ? (int)round(($completed_schedules / $total_expected_doses) * 100) : 0;
        $progress = min($progress, 100); // Cap at 100%

        return [
            'progress' => $progress,
            'total_schedules' => (int)$total_expected_doses,
            'completed_schedules' => (int)$completed_schedules,
            'taken_schedules' => $taken
        ];
    }


    public function missedSchedulesForHealthProviders(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;
        $query = collect();

        if ($role == "Doctor") {
            $query = DoctorRelation::where("doctor_id", $user->doctor->id)->pluck("patient_id");
        } elseif ($role == "Caregiver") {
            $query = CaregiverRelation::where("caregiver_id", $user->caregiver->id)->pluck("patient_id");
        }

        $patients = Patient::whereIn("id", $query)->get();

        $missedSchedules = $patients->map(function ($patient) {
            return [
                "patient_id" => $patient->id,
                "patient_name" => $patient->user->name,
                "counts" => $patient->missedSchedules(),
            ];
        });

        return response()->json($missedSchedules->values());
    }


    public function latestPatientSideEffects(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;
        $query = collect();

        if ($role == "Doctor") {
            $query = DoctorRelation::where("doctor_id", $user->doctor->id)->pluck("patient_id");
        } elseif ($role == "Caregiver") {
            $query = CaregiverRelation::where("caregiver_id", $user->caregiver->id)->pluck("patient_id");
        }

        $validatedData = $request->validate([
            'search' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1'
        ]);

        $search = $validatedData['search'] ?? null;
        $startDate = $validatedData['start_date'] ?? null;
        $endDate = $validatedData['end_date'] ?? null;
        $perPage = $validatedData['per_page'] ?? 10;
        $page = $validatedData['page'] ?? 1;

        $sideEffectsQuery = SideEffect::whereIn("patient_id", $query)
            ->orderBy('created_at', 'DESC')
            ->with(['patient.user', 'medication']);

        if (!empty($search)) {
            $sideEffectsQuery->whereHas('patient.user', function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%");
            })->orWhereHas('medication', function ($query) use ($search) {
                $query->where('medication_name', 'LIKE', "%$search%");
            });
        }

        if (!empty($startDate) && !empty($endDate)) {
            $sideEffectsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $sideEffects = $sideEffectsQuery->paginate($perPage, ['*'], 'page', $page);

        $formatted = $this->formatPaginationData($sideEffects);

        return response()->json($formatted);
    }


    public function formatPaginationData($data){
        return [
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
        ];
    }
}

