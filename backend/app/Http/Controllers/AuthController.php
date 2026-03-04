<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Create user (hash password!)
        $user = User::create($request->all());


        Notification::create([
            'user_id' => $user->id,
            'type' => 'register_success',
            'data' => [
                'message' => 'Welcome! Your account was created successfully.',
            ],
            'created_at' => now(),
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load(['role']),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();
        
        Notification::create([
            'user_id' => $user->id,
            'type' => 'login_success',
            'data' => [
                'message' => 'You logged in successfully.',
            ],
            'created_at' => now(),
        ]);


        // Check cache first
        $cachedUser = Cache::get('user:' . $user->id);

        if ($cachedUser) {
            $userData = $cachedUser;
        } else {
            $userData = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email
            ];
            Cache::put('user:' . $user->id, $userData, 300); // cache 5 minutes
        }

        // Create token
        $user = User::with(['role'])->findOrFail(Auth::id());
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
