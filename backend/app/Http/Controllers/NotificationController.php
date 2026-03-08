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
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $notifications,
            'count' => $notifications->count(),
            'unread_count' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markRead(Request $request, Notification $notification)
    {
        // Verify the notification belongs to the user
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification,
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, Notification $notification)
    {
        // Verify the notification belongs to the user
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    /**
     * Delete all notifications
     */
    public function destroyAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'All notifications deleted']);
    }

    /**
     * Undo a notification action (like reverting a like)
     */
    public function undo(Request $request, $id)
    {
        $notification = Notification::find($id);

        if (!$notification || $notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        // Delete the notification
        $notification->delete();

        return response()->json(['message' => 'Notification action undone']);
    }
}
