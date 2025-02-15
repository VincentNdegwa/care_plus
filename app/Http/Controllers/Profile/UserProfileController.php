<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserProfileController extends Controller
{
    public function update(Request $request)
    {
        try {
            $user = $request->user();
            $request->validate([
                "gender" => 'nullable|in:male,female,other',
                "date_of_birth" => 'nullable|date|before:' . Carbon::now()->format('Y-m-d'),
                "address" => 'nullable|string',
                "phone_number" => 'nullable|string',
                "avatar" => 'nullable|string',
            ]);

            $profile = UserProfile::firstOrNew(['user_id' => $user->id]);
            $profile->fill($request->only([
                'gender',
                'date_of_birth',
                'address',
                'phone_number',
                'avatar'
            ]));
            $profile->save();

            return response()->json([
                'error' => false,
                'message' => 'Profile updated successfully',
                'profile' => $profile
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage(),
            ],422);
        } catch (\Exception $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage(),
            ],500);
        }
    }

    public function open(Request $request)
    {
        try {
            $user = $request->user();

            $userQuery = User::query()->where("id", $user->id);

            switch ($user->role) {
                case 'Doctor':
                    $userQuery->with('doctor');
                    break;
                case 'Patient':
                    $userQuery->with('patient');
                    break;
                case 'Caregiver':
                    $userQuery->with('caregiver');
                    break;
            }

            $userWithProfile = $userQuery->with("profile")->first();

            return response()->json([
                "error" => false,
                "data" => $userWithProfile,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
            ]);
        }
    }
}
