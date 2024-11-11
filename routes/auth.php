<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\AuthenticateUserController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\Auth\VerifyEmailController;

Route::post('/register', [RegisterUserController::class, 'create']);

Route::post('/login', [AuthenticateUserController::class, 'create']);

// Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);

// Route::post('/reset-password', [NewPasswordController::class, 'store']);

// Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class);

// Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthenticateUserController::class, 'destroy']);
    Route::get("/user", function (Request $request) {
        return $request->user();
    });
});
