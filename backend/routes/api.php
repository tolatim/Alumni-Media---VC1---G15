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
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
// use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/settings/appearance', [AppAppearanceController::class, 'showPublic']);

Route::get('/users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->patch('/user/profile', [UserController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::get('/feed', [UserController::class, 'feed']);
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

    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/messages/contacts', [MessageController::class, 'contacts']);
    Route::delete('/messages/item/{messageId}', [MessageController::class, 'destroy']);
    Route::put('/messages/item/{messageId}', [MessageController::class, 'update']);
    Route::post('/messages/{userId}/read', [MessageController::class, 'markRead']);
    Route::get('/messages/{userId}', [MessageController::class, 'index']);
    Route::post('/messages/{userId}', [MessageController::class, 'store']);
    Route::get('/groups', [GroupChatController::class, 'index']);
    Route::post('/groups', [GroupChatController::class, 'store']);
    Route::post('/groups/{groupId}/invite', [GroupChatController::class, 'invite']);
    Route::get('/groups/{groupId}/messages', [GroupChatController::class, 'messages']);
    Route::post('/groups/{groupId}/messages', [GroupChatController::class, 'sendMessage']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/seen', [NotificationController::class, 'markSeen']);
    Route::post('/notifications/mark-all-seen', [NotificationController::class, 'markAllSeen']);

    Route::get('/users/suggestions', [UserController::class, 'suggestions']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::get('/profiles/{id}', [UserController::class, 'show']);
    Route::patch('/user/profile', [UserController::class, 'update']);

    Route::put('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile', [UserController::class, 'updateMyProfile']);
    Route::post('/profile/change-password', [UserController::class, 'changePassword']);

    // post
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::post('/posts/{post}/share', [PostController::class, 'share']);
    Route::get('/posts/{id}/shares', [PostController::class, 'shareList']);
    Route::post('/posts/{id}', [PostController::class, 'update']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::patch('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::post('/posts/{id}/report', [PostController::class, 'report']);
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle']);
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);


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
});
