<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthenticateUserController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CareProvider\FetchCareProvidersController;
use App\Http\Controllers\CareProvider\SetCareGiversController;
use App\Http\Controllers\Dash\PatientDataController;
use App\Http\Controllers\Diagnosis\CreateDiagnosisController;
use App\Http\Controllers\Diagnosis\DeleteDiagnosisController;
use App\Http\Controllers\Diagnosis\FetchDiagnosisController;
use App\Http\Controllers\Diagnosis\FetchPatientDiagnoses;
use App\Http\Controllers\Diagnosis\UpdateDiagnosisController;
use App\Http\Controllers\HealthVitals\HealthVitalsController;
use App\Http\Controllers\Medication\CreateMedicationController;
use App\Http\Controllers\Medication\DeleteMedicationController;
use App\Http\Controllers\Medication\FetchMedicationController;
use App\Http\Controllers\Medication\FetchMedicationResourcesController;
use App\Http\Controllers\Medication\MedicationFilterController;
use App\Http\Controllers\Medication\MedicationResourcesController;
use App\Http\Controllers\Medication\ScheduleMedicationController;
use App\Http\Controllers\Medication\UpdateMedicationController;
use App\Http\Controllers\Message\AtSMSController;
use App\Http\Controllers\Profile\ProfessionalProfileController;
use App\Http\Controllers\Profile\UpdateProfessionalProfileController;
use App\Http\Controllers\Profile\UserProfileController;
use App\Http\Controllers\SideEffect\AlterSideEffectController;
use App\Http\Controllers\SideEffect\CreateSideEffectsController;
use App\Http\Controllers\SideEffect\FetchSideEffectsController;
use App\Http\Controllers\Medication\SchedulesFunctionsController;
use App\Http\Controllers\FCM\DeviceTokenController;
use App\Http\Controllers\Uploads\FileUploadController;
use App\Jobs\TestJobNotification;
use PHPUnit\Event\Code\Test;

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
            Route::patch("/update/{diagnosis_id}", [UpdateDiagnosisController::class, 'update']);
            Route::delete("/delete/{diagnosis_id}", [DeleteDiagnosisController::class, 'delete']);
        });

        Route::prefix("medications")->group(function () {
            Route::get("/{medication_id}", [FetchMedicationController::class, "find"]);
            Route::post("/create", [CreateMedicationController::class, 'create']);
            Route::patch("/update/{medication_id}", [UpdateMedicationController::class, 'update']);
            Route::delete("/delete/{medication_id}", [DeleteMedicationController::class, 'delete']);
            // Schedule routes group
            Route::prefix('schedule')->group(function () {
                Route::post("/generate-time", [ScheduleMedicationController::class, 'generateScheduleTimes']);
                Route::post("/default", [ScheduleMedicationController::class, "scheduleDefault"]);
                Route::post("/custom", [ScheduleMedicationController::class, "scheduleCustom"]);
                
                // Function routes
                Route::post("/take", [SchedulesFunctionsController::class, "take"])
                    ->name('medication.schedule.take');
                    
                Route::post("/stop", [SchedulesFunctionsController::class, "stop"])
                    ->name('medication.schedule.stop');
                    
                Route::post("/snooze", [SchedulesFunctionsController::class, "snooze"])
                    ->name('medication.schedule.snooze');
                    
                Route::post("/resume", [SchedulesFunctionsController::class, "resume"])
                    ->name('medication.schedule.resume');
            });

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
            Route::post("/fetch", [FetchSideEffectsController::class, "getMedicationSideEffects"]);
            Route::get("/{side_effect_id}", [FetchSideEffectsController::class, "getOne"]);
            Route::patch("/update/{side_effect_id}", [AlterSideEffectController::class, "update"]);
            Route::delete("/delete/{side_effect_id}", [AlterSideEffectController::class, "delete"]);
        });

        Route::prefix('care-providers')->group(function () {
            Route::get('/fetch-patient-doctors/{patient_id}', [FetchCareProvidersController::class, 'fetchPatientDoctors']);
            Route::get('/fetch-patient-caregivers/{patient_id}', [FetchCareProvidersController::class, 'fetchPatientCareGivers']);
            Route::post('/set-doctor', [SetCareGiversController::class, 'setDoctor'])->name('set-doctor');
            Route::post('/set-caregiver', [SetCareGiversController::class, 'setCareGiver'])->name('set-caregiver');
            Route::post('/remove-doctor', [SetCareGiversController::class, 'removeDoctor']);
            Route::post('/remove-caregiver', [SetCareGiversController::class, 'removeCareGiver']);
            Route::get("/fetch-all", [FetchCareProvidersController::class, "fetchAll"]);
        });

        Route::prefix('medication-schedules')->group(function () {
            Route::post("/fetch", [ScheduleMedicationController::class, 'getMedicationScheduleByDate']);
            Route::get("/{patient_id}", [ScheduleMedicationController::class, 'getTodaysPatientMedicationSchedule']);
        });
        Route::prefix("health-vitals")->group(function () {
            Route::post("/create", [HealthVitalsController::class, "create"]);
            Route::patch("/update", [HealthVitalsController::class, "update"]);
            Route::get("/{patient_id}", [HealthVitalsController::class, "index"]);
        });
        Route::prefix('dashboard')->group(function () {
            Route::get('/patient-data/{patient_id}', [PatientDataController::class, 'index']);
        });

        Route::prefix('notification')->group(function () {
            Route::post('register-token', [DeviceTokenController::class, 'register']);
            Route::post('deactivate-token', [DeviceTokenController::class, 'deactivate']);
        });

        Route::prefix("upload")->group(function () {
            Route::post("/file", [FileUploadController::class, "upload"]);
            Route::delete("/file", [FileUploadController::class, "delete"]);
        });
    });

    Route::get("/send-sms", [AtSMSController::class, "send"]);
    Route::get("/medication-extend/{medication_tracker_id}", [ScheduleMedicationController::class, 'extend']);
    // update side effects create route, diagnosis update route in the postman collection

    Route::get("/send-notification", function(){
        TestJobNotification::dispatch();
    });

});

Route::options('{any}', function () {
    return response()->json([], 204);
})->where('any', '.*');

