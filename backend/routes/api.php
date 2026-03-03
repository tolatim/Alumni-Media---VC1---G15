<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Models\Notification;
use Illuminate\Http\Request;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

<<<<<<< HEAD
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
=======
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::get('/feed', [UserController::class, 'feed']);
    Route::get('/connections/my', [UserController::class, 'myConnections']);
    Route::get('/connections/pending', [UserController::class, 'pendingConnections']);
    Route::post('/connections/request', [UserController::class, 'sendConnectionRequest']);
    Route::post('/connections/{id}/accept', [UserController::class, 'acceptConnection']);
    Route::post('/connections/{id}/reject', [UserController::class, 'rejectConnection']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/suggestions', [UserController::class, 'suggestions']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::get('/profiles/{id}', [UserController::class, 'show']);

    Route::put('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile/change-password', [UserController::class, 'changePassword']);
});
>>>>>>> feature/user-connection
