<?php

namespace App\Http\Controllers\FCM;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'required|in:android,ios,web'
        ]);

        $token = DeviceToken::updateOrCreate(
            [
                'token' => $request->token,
                'user_id' => auth()->id()
            ],
            [
                'device_type' => $request->device_type,
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
            ->where('user_id', auth()->id())
            ->update(['is_active' => false]);

        return response()->json([
            'error' => false,
            'message' => 'Device token deactivated successfully'
        ]);
    }
} 