<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterUserController extends Controller
{
    public function create(Request $request)
    {

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
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

            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                "error" => false,
                'message' => 'Registration successful',
                'token' => $token,
                "user" => $user
            ], 200);
        } catch (ValidationException $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage(),
                'errors' => $th->errors()
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "error" => true,
                'message' => 'An error occurred during registration',
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
