<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterUserController extends Controller
{
    public function create(Request $request)
    {

        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:' . User::class,
                'role' => 'required|string|in:Doctor,Caregiver,Patient',
                'password' => 'required|min:4',
                'password_confirmation' => 'required|min:4|same:password',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
                'password' => Hash::make($request->password),
            ]);

            switch ($user->role) {
                case 'Doctor':
                    Doctor::create([
                        'user_id' => $user->id,
                    ]);
                    break;
                case 'Caregiver':
                    Caregiver::create([
                        'user_id' => $user->id,
                    ]);
                    break;
                case 'Patient':
                    Patient::create([
                        'user_id' => $user->id,
                    ]);
                    break;
                default:
                    break;
            }

            UserProfile::create([
                'user_id' => $user->id,
            ]);

            $token = $this->createToken($user);
            DB::commit();
            return response()->json([
                "error" => false,
                'message' => 'Registration successful',
                'token' => $token,
                "user" => $user
            ], 200);
        } catch (ValidationException $th) {
            DB::rollBack();
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                'errors' => $th->errors()
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                "error" => true,
                'message' => 'An error occurred during registration',
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    private function createToken(User $user)
    {
        $abilities = [];
        switch ($user->role) {
            case 'Doctor':
                $abilities = ['doctor'];
                break;
            case 'Caregiver':
                $abilities = ['caregiver'];
                break;
            case 'Patient':
                $abilities = ['patient'];
                break;
            default:
                $abilities = [];
                break;
        }

        return $user->createToken('API Token', $abilities)->plainTextToken;
    }
}
