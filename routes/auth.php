<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthenticateUserController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Profile\ProfessionalProfileController;
use App\Http\Controllers\Profile\UpdateProfessionalProfileController;
use App\Http\Controllers\Profile\UserProfileController;

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
            Route::get('/doctor/{id}', [ProfessionalProfileController::class, "doctor"]);
            Route::get('/caregiver/{id}', [ProfessionalProfileController::class, "caregiver"]);
            Route::patch("/caregiver", [UpdateProfessionalProfileController::class, "caregiver"]);
            Route::patch("/doctor", [UpdateProfessionalProfileController::class, "doctor"]);
        });

        Route::post('/email/request-verification', [EmailVerificationNotificationController::class, 'store']);
        Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)->name('verification.verify');
        Route::patch('/profile', [UserProfileController::class, 'update']);
        Route::get("/profile", [UserProfileController::class, "open"]);
    });
});
