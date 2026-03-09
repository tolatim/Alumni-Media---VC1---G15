<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

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

        $alumniRole = Role::firstOrCreate(['name' => 'alumni']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role_id' => $alumniRole->id,
            'role_id' => $userRole->id,
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // Cache user data for 5 minutes
        Cache::put('user:' . $user->id, [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email
        ], 300);

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
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

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
