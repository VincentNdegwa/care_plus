<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request)
    {

        try {
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    'error' => false,
                    "message" => "Email address is already verified",
                ]);
            }

            if ($request->user()->markEmailAsVerified()) {
                return response()->json([
                    'error' => false,
                    'message' => "Email address has been verified successfully",
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
            ]);
        }
    }
}
