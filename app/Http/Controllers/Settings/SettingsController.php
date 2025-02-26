<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get user settings
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $userSettings = $user->settings;

            if (!$userSettings) {
                $userSettings = UserSetting::create([
                    'user_id' => $user->id,
                    'settings' => []
                ]);
            }

            return response()->json($userSettings->getMergedSettings());
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user settings
     */
    public function update(Request $request)
    {
        try {
            $user = Auth::user();
            $userSettings = $user->settings;

            if (!$userSettings) {
                return response()->json([
                    'error' => true,
                    'message' => 'User settings not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'settings' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->getMessageBag()->first(),
                ], 422);
            }

            $userSettings->settings = $request->settings;
            $userSettings->save();

            return response()->json([
                'error' => false,
                'message' => 'Settings updated successfully',
                'data' => $userSettings->getMergedSettings()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
