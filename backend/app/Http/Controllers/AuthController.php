<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Role;
use App\Models\User;
use App\Support\WebsocketNotifier;
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
            'registration_key' => 'nullable|string|max:255',
        ]);

        $configuredKey = trim((string) AppSetting::query()->where('key', 'registration_key')->value('value'));
        $fallbackKey = trim((string) env('REGISTRATION_KEY', ''));
        $effectiveRegistrationKey = $configuredKey !== '' ? $configuredKey : $fallbackKey;

        if ($effectiveRegistrationKey !== '' && !hash_equals($effectiveRegistrationKey, (string) ($validated['registration_key'] ?? ''))) {
            return response()->json([
                'message' => 'Invalid registration key.',
            ], 403);
        }

        // Assign default role
        $userRole = Role::firstOrCreate(['name' => 'alumni']);

        // Create the user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role_id' => $userRole->id,
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']), // Always hash passwords
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

        // ----------------------------
        // Send event to Node.js server
        // ----------------------------
        WebsocketNotifier::send('new_user', [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]);

        WebsocketNotifier::send('admin_activity', [
            'event' => 'user_registered',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'occurred_at' => now()->toIso8601String(),
        ], 'admins');

        return response()->json([
            'message' => 'User created successfully',
            'token' => $token,
            'user' => $user->load('role'),
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

        if ($user->isSuspended()) {
            Auth::logout();
            return response()->json([
                'message' => 'Your account is suspended',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        WebsocketNotifier::send('login', [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]);

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
