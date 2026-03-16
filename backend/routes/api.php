<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SavedPostController;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ---------------- Authentication ----------------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ---------------- Users ----------------
Route::apiResource('/users', UserController::class);
Route::get('/users', [UserController::class, 'index']);

// ---------------- Protected Routes ----------------
Route::middleware('auth:sanctum')->group(function () {
    // --- Current User ---
    Route::get('/me', [AuthController::class, 'me']);

    // --- Profile ---
    Route::patch('/user/profile', [UserController::class, 'update']);
    Route::put('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile/change-password', [UserController::class, 'changePassword']);

    // --- Posts ---
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::patch('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle']);
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    

    // --- Messages ---
    Route::get('/messages/{userId}', [MessageController::class, 'index']);
    Route::post('/messages/{userId}', [MessageController::class, 'store']);
    Route::put('/messages/item/{messageId}', [MessageController::class, 'update']);
    Route::delete('/messages/item/{messageId}', [MessageController::class, 'destroy']);
    Route::post('/messages/{userId}/read', [MessageController::class, 'markRead']);
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/messages/contacts', [MessageController::class, 'contacts']);

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

    // --- Connections ---
    Route::get('/connections/my', [UserController::class, 'myConnections']);
    Route::get('/connections/blocked', [UserController::class, 'blockedConnections']);
    Route::get('/connections/pending', [UserController::class, 'pendingConnections']);
    Route::get('/connections/status/{userId}', [UserController::class, 'connectionStatus']);
    Route::post('/connections/request', [UserController::class, 'sendConnectionRequest']);
    Route::post('/connections/{id}/accept', [UserController::class, 'acceptConnection']);
    Route::post('/connections/{id}/reject', [UserController::class, 'rejectConnection']);
    Route::post('/connections/user/{userId}/unfriend', [UserController::class, 'unfriend']);
    Route::post('/connections/user/{userId}/block', [UserController::class, 'blockUser']);
    Route::post('/connections/user/{userId}/unblock', [UserController::class, 'unblockUser']);

    // --- Feed ---
    Route::get('/feed', [UserController::class, 'feed']);

    // --- Notifications ---
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);


    // --- saved items ---
    Route::post('/save-post/{postId}', [SavedPostController::class, 'save']);
    Route::delete('/unsave-post/{postId}', [SavedPostController::class, 'unsave']);
    Route::get('/saved-posts', [SavedPostController::class, 'index']);
});