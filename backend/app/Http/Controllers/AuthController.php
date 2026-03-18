<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Services\NotificationService;


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

        NotificationService::welcome($user); // ✅ this is all you need


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
        
        // ✅ Connected to NotificationService
        NotificationService::login($user);

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
