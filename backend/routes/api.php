<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::get('/feed', [UserController::class, 'feed']);
    Route::get('/connections/my', [UserController::class, 'myConnections']);
    Route::get('/connections/pending', [UserController::class, 'pendingConnections']);
    Route::get('/connections/status/{userId}', [UserController::class, 'connectionStatus']);
    Route::post('/connections/request', [UserController::class, 'sendConnectionRequest']);
    Route::post('/connections/{id}/accept', [UserController::class, 'acceptConnection']);
    Route::post('/connections/{id}/reject', [UserController::class, 'rejectConnection']);
    Route::post('/connections/user/{userId}/unfriend', [UserController::class, 'unfriend']);
    Route::post('/connections/user/{userId}/block', [UserController::class, 'blockUser']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::post('/notifications/{id}/undo', [NotificationController::class, 'undo']);

    Route::get('/messages/contacts', [MessageController::class, 'contacts']);
    Route::get('/messages/{userId}', [MessageController::class, 'index']);
    Route::post('/messages/{userId}', [MessageController::class, 'store']);
    Route::get('/messages/unread-count', function (Request $request) {
        return response()->json([
            'unread_count' => Message::query()
                ->where('receiver_id', $request->user()->id)
                ->whereNull('read_at')
                ->count(),
        ]);
    });

    Route::patch('/messages/{message}/read', function (Request $request, Message $message) {
        $user = $request->user();
        abort_unless((int) $message->receiver_id === (int) $user->id, 403);

        if (!$message->read_at) {
            $message->update(['read_at' => now()]);
        }

        Notification::query()
            ->where('user_id', $user->id)
            ->where('type', 'new_message')
            ->where('notifiable_type', Message::class)
            ->where('notifiable_id', $message->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Message marked as read',
            'unread_messages' => Message::query()
                ->where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'unread_notifications' => $user->notifications()->whereNull('read_at')->count(),
        ]);
    });

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/suggestions', [UserController::class, 'suggestions']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::get('/profiles/{id}', [UserController::class, 'show']);

    Route::put('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile/change-password', [UserController::class, 'changePassword']);
});
