<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Models\Notification;
use Illuminate\Http\Request;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::apiResource('/users', UserController::class);
Route::get('/users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->patch('/user/profile', [UserController::class, 'update']);
// Route::middleware('auth:sanctum')->patch('/user/profile', [UserController::class, 'updateProfile']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', function (Request $request) {
        return $request->user()
            ->notifications()
            ->latest('created_at')
            ->get();
    });

    Route::patch('/notifications/{notification}/read', function (Request $request, Notification $notification) {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['message' => 'Marked as read']);
    });
});