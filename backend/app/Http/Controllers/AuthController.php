<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
<<<<<<< HEAD
        try {
            // Validate
            $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed', // requires password_confirmation
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return custom JSON instead of default 422
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors()->first(), // first error message
            ], 400);
        }
        // Create user (hash password!)
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
=======
        // Validate
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed', // requires password_confirmation
        ]);

        // Create user (hash password!)
        $user = User::create($request -> all());
>>>>>>> feature/user-authentication

        // Cache user data for 5 minutes
        Cache::put('user:' . $user->id, [
            'id' => $user->id,
<<<<<<< HEAD
            'first_name' => $user->first_name,
=======
            'fist_name' => $user->fist_name,
>>>>>>> feature/user-authentication
            'last_name' => $user->last_name,
            'email' => $user->email
        ], 300);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        // Check cache first
        $cachedUser = Cache::get('user:' . $user->id);

        if ($cachedUser) {
            $userData = $cachedUser;
        } else {
            $userData = [
                'id' => $user -> id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user -> email
            ];
            Cache::put('user:' . $user->id, $userData, 300); // cache 5 minutes
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'Login successfully',
            'token' => $token,
            'user' => $userData
        ]);
    }
}
