<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::query()
            ->where('user_id', $user->id)
            ->orWhere(function ($query) use ($user) {
                $query->where('notifiable_type', get_class($user))
                    ->where('notifiable_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Notifications fetched successfully',
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        $user = $request->user();

        // Authorize the user
        abort_unless(
            $notification->user_id === $user->id ||
            ($notification->notifiable_type === get_class($user) && $notification->notifiable_id === $user->id),
            403
        );

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => $notification,
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, Notification $notification)
    {
        $user = $request->user();

        // Authorize the user
        abort_unless(
            $notification->user_id === $user->id ||
            ($notification->notifiable_type === get_class($user) && $notification->notifiable_id === $user->id),
            403
        );

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $unreadCount = Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }
}
