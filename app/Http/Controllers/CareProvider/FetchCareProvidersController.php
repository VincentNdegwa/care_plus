<?php

namespace App\Http\Controllers\CareProvider;

use App\Http\Controllers\Controller;
use App\Models\CaregiverRelation;
use App\Models\DoctorRelation;
use App\Models\User;
use Illuminate\Http\Request;

class FetchCareProvidersController extends Controller
{
    public function fetchPatientDoctors(Request $request, $patient_id)
    {
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);
        return $this->fetchData(DoctorRelation::class, 'doctor', $patient_id, $page, $per_page);
    }

    public function fetchPatientCareGivers(Request $request, $patient_id)
    {
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);

        return $this->fetchData(CaregiverRelation::class, 'caregiver', $patient_id, $page, $per_page);
    }

    private function fetchData($model, $relation, $patient_id, $page, $per_page)
    {
        $user_ids = $model::where("patient_id", $patient_id)->with($relation)->get()->pluck($relation . '.user_id');
        return $this->fetch($user_ids, $page, $per_page);
    }

    public function fetchAll(Request $request)
    {
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);

        return $this->fetch(null, $page, $per_page);
    }

    public function fetch($user_ids = null, $page, $per_page)
    {
        $userQuery = User::query();
        if (isset($user_ids) && $user_ids != null) {
            $userQuery->whereIn('id', $user_ids);
        }

        $users = $userQuery->where('role', '!=', 'patient')->paginate($per_page, ['*'], 'page', $page);

        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'user_role' => $user->userRole(),
                'profile' => $user->profile,
            ];
        });

        return response()->json([
            'data' => $users->items(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ]);
    }
}
