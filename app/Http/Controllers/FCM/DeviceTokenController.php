<?php

namespace App\Http\Controllers\FCM;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'required'
        ]);

        

        $token = DeviceToken::updateOrCreate(
            [
                'user_id' => Auth::user()->id,
                'device_type' => $request->device_type,
            ],
            [
                'token' => $request->token,
                'is_active' => true
            ]
        );

        return response()->json([
            'error' => false,
            'message' => 'Device token registered successfully',
            'data' => $token
        ]);
    }

    public function deactivate(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        DeviceToken::where('token', $request->token)
            ->where('user_id', Auth::user()->id)
            ->update(['is_active' => false]);

        return response()->json([
            'error' => false,
            'message' => 'Device token deactivated successfully'
        ]);
    }
} 