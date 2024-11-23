<?php

namespace App\Http\Controllers\Medication;

use App\Http\Controllers\Controller;
use App\Models\Medication\MedicationForm;
use App\Models\Medication\MedicationRoute;
use App\Models\Medication\MedicationUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicationResourcesController extends Controller
{
    /**
     * Get all medication forms.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMedicationForms()
    {
        $forms = MedicationForm::all();
        return response()->json($forms, 200);
    }

    /**
     * Get all medication routes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMedicationRoutes()
    {
        $routes = MedicationRoute::all();
        return response()->json($routes, 200);
    }

    /**
     * Get all medication units.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMedicationUnits()
    {
        $units = MedicationUnit::select("id", "name")->get();
        return response()->json($units, 200);
    }

    /**
     * Get all medication frequencies.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMedicationFrequencies()
    {
        $frequencies = DB::table('medication_frequencies')->select("id", "frequency")->get();
        return response()->json($frequencies, 200);
    }
}
