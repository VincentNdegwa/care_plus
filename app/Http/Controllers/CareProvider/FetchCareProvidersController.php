<?php

namespace App\Http\Controllers\CareProvider;

use App\Http\Controllers\Controller;
use App\Http\Traits\CareProviderValidation;
use App\Models\CaregiverRelation;
use App\Models\DoctorRelation;
use App\Models\User;
use App\Services\CareProviderQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FetchCareProvidersController extends Controller
{
    use CareProviderValidation;

    protected CareProviderQueryService $queryService;

    public function __construct(CareProviderQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function fetchAll(Request $request): JsonResponse
    {
        $request->validate($this->getValidationRules());
        return $this->fetchCareProviders($this->getRequestParams($request));
    }

    public function fetchPatientDoctors(Request $request, $patientId): JsonResponse
    {
        $request->validate($this->getValidationRules());
        $params = $this->getRequestParams($request);
        $params['user_ids'] = $this->getUserIds(DoctorRelation::class, 'doctor', $patientId);
        $params['role'] = 'Doctor';
        return $this->fetchCareProviders($params);
    }

    public function fetchPatientCareGivers(Request $request, $patientId): JsonResponse
    {
        $request->validate($this->getValidationRules());
        $params = $this->getRequestParams($request);
        $params['user_ids'] = $this->getUserIds(CaregiverRelation::class, 'caregiver', $patientId);
        $params['role'] = 'Caregiver';
        return $this->fetchCareProviders($params);
    }

    protected function getRequestParams(Request $request): array
    {
        return [
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 10),
            'search' => $request->query('search'),
            'role' => $request->query('role'),
            'agency_name' => $request->query('agency_name'),
            'gender' => $request->query('gender'),
            'specialization' => $request->query('specialization'),
        ];
    }

    protected function getUserIds(string $model, string $relation, $patientId): array
    {
        $id_array = $model::where('patient_id', $patientId)
            ->with($relation)
            ->get()
            ->pluck($relation . '.user_id')
            ->toArray();
        Log::info('Found user IDs: ' . json_encode($id_array));

        return array_filter($id_array);
    }

    protected function fetchCareProviders(array $params): JsonResponse
    {
        $query = User::query();
        $this->queryService->buildQuery($query, $params);

        $users = $query->paginate($params['per_page'], ['*'], 'page', $params['page']);

        return response()->json($this->transformPaginatedData($users));
    }

    protected function transformPaginatedData($paginatedData): array
    {
        $transformedData = $paginatedData->through(function ($user) {
            return $this->transformUserData($user);
        });

        return [
            'data' => $transformedData->items(),
            'current_page' => $transformedData->currentPage(),
            'last_page' => $transformedData->lastPage(),
            'per_page' => $transformedData->perPage(),
            'total' => $transformedData->total(),
        ];
    }

    protected function transformUserData($user): array
    {
        $professionalData = null;
        if ($user->role === 'Doctor') {
            $professionalData = $user->doctor;
        } elseif ($user->role === 'Caregiver') {
            $professionalData = $user->caregiver;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'profile' => $user->profile,
            'user_role' => $professionalData,
        ];
    }
}
