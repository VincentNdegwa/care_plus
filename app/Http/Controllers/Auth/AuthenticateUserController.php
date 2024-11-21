<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUserController extends Controller
{
    public function create(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required',
            ]);
            $user = User::where('email', $request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $token = $this->createToken($user);
            return response()->json([
                "error" => false,
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user
            ], 200);
        } catch (ValidationException $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                'errors' => $th->errors()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                'message' => 'An error occurred during login',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $token = $request->bearerToken();

            $tokenId = explode('|', $token)[0];
            $request->user()->tokens()->where('id', $tokenId)->delete();
            return response()->json([
                "error" => false,
                'message' => 'Logout successful'
            ], 200);
        } catch (Exception $th) {
            return response()->json([
                "error" => true,
                'message' => $th->getMessage(),
                'errors' => $th
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
                // No abilities for unknown roles
                $abilities = [];
                break;
        }

        return $user->createToken('API Token', $abilities)->plainTextToken;
    }
}
