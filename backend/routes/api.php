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
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/settings/appearance', [AppAppearanceController::class, 'showPublic']);
Route::get('/users', [UserController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/messages/contacts', [MessageController::class, 'contacts']);
    Route::get('/messages/{userId}', [MessageController::class, 'index']);
    Route::get('/messages/{userId}/sync', [MessageController::class, 'sync']);
    Route::post('/messages/{userId}', [MessageController::class, 'store']);
    Route::post('/messages/{userId}/read', [MessageController::class, 'markRead']);
    Route::put('/messages/item/{messageId}', [MessageController::class, 'update']);
    Route::patch('/messages/item/{messageId}', [MessageController::class, 'update']);
    Route::delete('/messages/item/{messageId}', [MessageController::class, 'destroy']);

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

    Route::get('/feed', [UserController::class, 'feed']);
    Route::get('/users/suggestions', [UserController::class, 'suggestions']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::get('/profiles/{id}', [UserController::class, 'show']);
    Route::patch('/user/profile', [UserController::class, 'update']);
    Route::put('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile/change-password', [UserController::class, 'changePassword']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::post('/posts/{id}', [PostController::class, 'update']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::patch('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::post('/posts/{id}/report', [PostController::class, 'report']);
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
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle']);
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);


    //notification
    Route::get('/notifications',                  [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count',     [NotificationController::class, 'unreadCount']); // ✅ move here
    Route::post('/notifications/read-all',        [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read',       [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}',          [NotificationController::class, 'destroy']);
});
