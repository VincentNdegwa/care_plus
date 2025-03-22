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
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Notification\Test\NotificationTestController;
use App\Http\Controllers\Patient\FetchPatientCareGiversController;
use App\Http\Controllers\Uploads\FileUploadController;
use App\Jobs\TestJobNotification;
use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\Settings\SettingsController;

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
        Route::post("/change-password", [PasswordResetLinkController::class, "updatePassword"]);
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
            Route::get("/search", [FetchDiagnosisController::class, "searchDiagnoses"]);
            Route::post("/filter", [FetchDiagnosisController::class, "filterDiagnoses"]);
            Route::get("/patient/{patient_id}", [FetchDiagnosisController::class, "fetchByPatient"]);
            Route::get("/doctor/{doctor_id}", [FetchDiagnosisController::class, "fetchByDoctor"]);
            Route::get("/{diagnosis_id}", [FetchDiagnosisController::class, "find"]);
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
                Route::post("/takeNow", [SchedulesFunctionsController::class, "takeNow"])
                    ->name('medication.schedule.takeNow');
                    
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

        Route::prefix("/reports")->group(function () {
            Route::prefix("/health-provider")->middleware(['ability:doctor,caregiver'])->group(function(){
                Route::post("/adherence-per-patient", [ReportsController::class, "adherencePerPatient"]);
                Route::post("/top-adhering-patients", [ReportsController::class, "topAdheringPatients"]);
                Route::post("/bottom-adhering-patients", [ReportsController::class, "bottomAdheringPatients"]);
                Route::post("/fetch-side-effects", [ReportsController::class, "fetchSideEffects"]);
                Route::post("/patient-missed-medications", [ReportsController::class, "missedSchedulesForHealthProviders"]);
                Route::post("/patient-latest-side-effects", [ReportsController::class, 'latestPatientSideEffects']);
            });
            Route::post("/medication-vs-side-effect-counts", [ReportsController::class, "medicationVsSideEffectCounts"]);
            Route::post("/top-side-effects", [ReportsController::class, "topSideEffects"]);
            Route::post("/most-missed-medications", [ReportsController::class, "mostMissedMedications"]);
            Route::post("/medical-adherence-report", [ReportsController::class, "medicalAdhearanceReport"]);
            Route::post("/medication-adherence-by-medication", [ReportsController::class, "medicationAdherenceByMedication"]);
            Route::get("/medication-progress", [ReportsController::class, "medicationProgress"]);
        });

        Route::prefix('care-providers')->group(function () {
            Route::get('/fetch-patient-doctors/{patient_id}', [FetchCareProvidersController::class, 'fetchPatientDoctors']);
            Route::get('/fetch-patient-caregivers/{patient_id}', [FetchCareProvidersController::class, 'fetchPatientCareGivers']);
            Route::post('/set-doctor', [SetCareGiversController::class, 'setDoctor'])->name('set-doctor');
            Route::post('/set-caregiver', [SetCareGiversController::class, 'setCareGiver'])->name('set-caregiver');
            Route::post('/remove-doctor', [SetCareGiversController::class, 'removeDoctor']);
            Route::post('/remove-caregiver', [SetCareGiversController::class, 'removeCareGiver']);
            Route::get("/fetch-all", [FetchCareProvidersController::class, "fetchAll"]);
            Route::post("/fetch-doctor-patient", [FetchPatientCareGiversController::class, 'fetchForDoctor']);
            Route::post("/fetch-caregiver-patient", [FetchPatientCareGiversController::class, 'fetchForCaregiver']);
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

        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index']);
            Route::post('/', [SettingsController::class, 'update']);
        });
    });

    Route::get("/send-sms", [AtSMSController::class, "send"]);
    Route::get("/medication-extend/{medication_tracker_id}", [ScheduleMedicationController::class, 'extend']);
    Route::get("/send-notification", function(){
        TestJobNotification::dispatch();
    });

    Route::prefix('notifications')->group(function () {
        Route::post('/', [NotificationController::class, 'index']);
        Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/delete', [NotificationController::class, 'destroy']);
        Route::prefix('test')->group(function(){
            Route::post('token', [NotificationTestController::class, 'testTokenNotification']);
            Route::post('room', [NotificationTestController::class, 'testRoomNotification']);
            Route::get('events', [NotificationTestController::class, 'listEvents']);
        });
    });

    Route::get("timezone",[SettingsController::class, "timezone"]);
});

Route::options('{any}', function () {
    return response()->json([], 204);
})->where('any', '.*');

