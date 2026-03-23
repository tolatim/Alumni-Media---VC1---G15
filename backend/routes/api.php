<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminPostModerationController;
use App\Http\Controllers\AdminReportModerationController;
use App\Http\Controllers\AdminUserModerationController;
use App\Http\Controllers\AppAppearanceController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;





Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/users', [UserController::class, 'index']);

// ---------------- Protected Routes ----------------
Route::middleware('auth:sanctum')->group(function () {

    // --- Current User ---
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::post('/messages/{message}/read', function (Request $request, Message $message) {
        $user = $request->user();
        if (!$message->read_at) {
            $message->update(['read_at' => now()]);
        }

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
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    // --- Admin ---
    Route::get('/admin/reported-posts', [AdminPostModerationController::class, 'indexReportedPosts']);
    Route::delete('/admin/reported-posts/{postId}', [AdminPostModerationController::class, 'deleteReportedPost']);
    Route::get('/admin/users', [AdminUserModerationController::class, 'index']);
    Route::post('/admin/users/{userId}/suspend', [AdminUserModerationController::class, 'suspend']);
    Route::get('/admin/reports', [AdminReportModerationController::class, 'index']);
    Route::post('/admin/reports/{reportId}/ignore', [AdminReportModerationController::class, 'ignore']);
    Route::post('/admin/reports/{reportId}/delete-post', [AdminReportModerationController::class, 'deletePost']);
    Route::post('/admin/reports/{reportId}/suspend-user', [AdminReportModerationController::class, 'suspendUser']);
    Route::get('/admin/settings/appearance', [AppAppearanceController::class, 'showAdmin']);
    Route::post('/admin/settings/appearance', [AppAppearanceController::class, 'update']);

    // --- Saved Posts ---
    Route::get('/saved-posts', [SavedPostController::class, 'index']);
    Route::post('/saved-posts/{post}', [SavedPostController::class, 'store']);
    Route::delete('/saved-posts/{post}', [SavedPostController::class, 'destroy']);
    Route::post('/saved-posts/{post}/toggle', [SavedPostController::class, 'toggle']); // ✅ added
});
