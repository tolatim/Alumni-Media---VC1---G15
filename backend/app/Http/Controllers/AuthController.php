<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Role;
use App\Models\User;
use App\Support\WebsocketNotifier;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;


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
            'registration_key' => 'required|string|max:255',
        ]);

        $configuredKey = AppSetting::query()->where('key', 'registration_key')->value('value');

        if (!$configuredKey) {
            return response()->json([
                'message' => 'Registration is currently closed. Please contact admin.',
            ], 403);
        }

        if (!hash_equals((string) $configuredKey, (string) $validated['registration_key'])) {
            return response()->json([
                'message' => 'Invalid registration key.',
            ], 403);
        }

        // Assign default role
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create the user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role_id' => $userRole->id,
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']), // Always hash passwords
        ]);

        Cache::put('user:' . $user->id, [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email
        ], 300);


        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Welcome to Alumni Media!',
            'message' => 'Your account has been successfully created.',
            'type'    => 'register_success',
        ]);



        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // ----------------------------
        // Send event to Node.js server
        // ----------------------------
        try {
            Http::post('http://localhost:3000/event', [
                'type' => 'new_user',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ],
            ]);
        } catch (\Exception $e) {
            // Optional: log errors if Node.js is not running
            \Log::error('Failed to send new user event: ' . $e->getMessage());
        }

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
            'title'   => 'Login Successful!',
            'message' => 'Welcome back, ' . $user->first_name . '!',
            'type'    => 'login_success',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        try {
            Http::post('http://localhost:3000/event', [
                'type' => 'login',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ],
            ]);
        } catch (\Exception $e) {
            // Optional: log errors if Node.js is not running
            \Log::error('Failed to send new user event: ' . $e->getMessage());
        }

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
