<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CareProvider\FetchCareProvidersController;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes();
Route::prefix('v1')->group(function () {
    Route::get('care-providers', [FetchCareProvidersController::class, 'fetchAll']);
    Route::get('patients/{patient_id}/doctors', [FetchCareProvidersController::class, 'fetchPatientDoctors']);
    Route::get('patients/{patient_id}/caregivers', [FetchCareProvidersController::class, 'fetchPatientCareGivers']);
});


