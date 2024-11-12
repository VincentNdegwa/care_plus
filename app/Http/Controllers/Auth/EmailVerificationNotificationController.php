<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    "error" => false,
                    "message" => "Email is already verified",
                ]);
            }

            $request->user()->sendEmailVerificationNotification();

            return response()->json(['error' => false, 'message' => 'verification-link-sent']);
        } catch (\Exception $th) {
            return response()->json([
                "error" => false,
                "message" => $th->getMessage(),
                "errors" => $th
            ]);
        }
    }
}
