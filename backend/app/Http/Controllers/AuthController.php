<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\WebsocketNotifier;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
<<<<<<<<< Temporary merge branch 1
use App\Services\NotificationService;

=========
use Illuminate\Support\Facades\Http;
>>>>>>>>> Temporary merge branch 2

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
            'title'   => 'Welcome to Alumni Media!',
            'message' => 'Your account has been successfully created.',
            'type'    => 'register_success',
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
<<<<<<<<< Temporary merge branch 1
        
        // ✅ Connected to NotificationService
        NotificationService::login($user);
=========

        if ($user->isSuspended()) {
            Auth::logout();
            return response()->json([
                'message' => 'Your account is suspended',
            ], 403);
        }
>>>>>>>>> Temporary merge branch 2

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
