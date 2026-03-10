<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
=======
>>>>>>> b339a8c7fa75814b232b74d1ba3e18083c43fc6c
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
<<<<<<< HEAD
=======
use Illuminate\Http\Request;
>>>>>>> b339a8c7fa75814b232b74d1ba3e18083c43fc6c

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $userRole = Role::firstOrCreate(['name' => 'user']);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role_id' => $userRole->id,
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Cache::put('user:' . $user->id, [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email
        ], 300);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'register_success',
            'data' => [
                'message' => 'Welcome! Your account was created successfully.',
            ],
        ]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User created successfully',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::with(['role'])->findOrFail(Auth::id());

        Notification::create([
            'user_id' => $user->id,
            'type' => 'login_success',
            'data' => [
                'message' => 'You logged in successfully.',
            ],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'Login successfully',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load(['role']));
    }
}
