<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthenticateUserController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Diagnosis\CreateDiagnosisController;
use App\Http\Controllers\Diagnosis\FetchDiagnosisController;
use App\Http\Controllers\Diagnosis\FetchPatientDiagnoses;
use App\Http\Controllers\FetchSideEffectsController;
use App\Http\Controllers\Medication\CreateMedicationController;
use App\Http\Controllers\Medication\FetchMedicationController;
use App\Http\Controllers\Medication\FetchMedicationResourcesController;
use App\Http\Controllers\Medication\MedicationFilterController;
use App\Http\Controllers\Medication\MedicationResourcesController;
use App\Http\Controllers\Medication\ScheduleMedicationController;
use App\Http\Controllers\Profile\ProfessionalProfileController;
use App\Http\Controllers\Profile\UpdateProfessionalProfileController;
use App\Http\Controllers\Profile\UserProfileController;
use App\Http\Controllers\SideEffect\CreateSideEffectsController;

Route::prefix("/v1")->group(function () {

    Route::post('/register', [RegisterUserController::class, 'create']);

    Route::post('/login', [AuthenticateUserController::class, 'create']);

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);

    Route::post('/reset-password', [NewPasswordController::class, 'store']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthenticateUserController::class, 'destroy']);
        Route::prefix("/user")->group(function () {
            Route::get("/", function (Request $request) {
                return $request->user();
            });
        });

        Route::post('/email/request-verification', [EmailVerificationNotificationController::class, 'store']);
        Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)->name('verification.verify');
        Route::patch('/profile', [UserProfileController::class, 'update']);
        Route::get("/profile", [UserProfileController::class, "open"]);
    });



    Route::middleware(['auth:sanctum'])->group(function () {

        Route::middleware(['ability:doctor'])->group(function () {
            Route::prefix("/user")->group(function () {
                Route::get('/doctor', [ProfessionalProfileController::class, "doctor"]);
                Route::patch("/doctor", [UpdateProfessionalProfileController::class, "doctor"]);
            });
            Route::prefix("/diagnosis")->group(function () {
                Route::post("/create", [CreateDiagnosisController::class, "create"]);
            });
        });

        Route::middleware(['ability:caregiver'])->group(function () {
            Route::prefix("/user")->group(function () {
                Route::get('/caregiver', [ProfessionalProfileController::class, "caregiver"]);
                Route::patch("/caregiver", [UpdateProfessionalProfileController::class, "caregiver"]);
            });
        });


        Route::prefix("/diagnosis")->group(function () {
            Route::get("/{diagnosis_id}", [FetchDiagnosisController::class, "find"]);
            Route::get("/patient/{patient_id}", [FetchDiagnosisController::class, "fetchByPatient"]);
            Route::get("/doctor/{doctor_id}", [FetchDiagnosisController::class, "fetchByDoctor"]);
            Route::get("/search/{professionalId}", [FetchDiagnosisController::class, "searchDiagnoses"]);
            Route::post("/filter", [FetchDiagnosisController::class, "filterDiagnoses"]);
        });

        Route::prefix("medications")->group(function () {
            Route::get("/{medication_id}", [FetchMedicationController::class, "find"]);
            Route::post("/create", [CreateMedicationController::class, 'create']);
            Route::post("/schedule/default", [ScheduleMedicationController::class, "scheduleDefault"]);
            Route::post("/schedule/custom", [ScheduleMedicationController::class, "scheduleCustom"]);
            Route::prefix('medication-resources')->group(function () {
                Route::get('/forms', [MedicationResourcesController::class, 'getMedicationForms']);
                Route::get('/routes', [MedicationResourcesController::class, 'getMedicationRoutes']);
                Route::get('/units', [MedicationResourcesController::class, 'getMedicationUnits']);
                Route::get('/frequencies', [MedicationResourcesController::class, 'getMedicationFrequencies']);
            });

            Route::prefix('fetch')->group(function () {
                Route::post('/by-patient', [FetchMedicationController::class, 'findByPatient']);
                Route::post('/by-doctor', [FetchMedicationController::class, 'findByDoctor']);
                Route::post('/filter', [MedicationFilterController::class, 'filterMedications']);
            });
        });

        Route::prefix("/side-effects")->group(function () {
            Route::post("/create", [CreateSideEffectsController::class, "create"]);
            // Route::get("/{medication_id}", [FetchSideEffectsController::class, "getMedicationSideEffects"]);
            // Route::post("/filter", [FetchSideEffectsController::class, "filterMedicationSideEffects"]);
        });
    });
});
