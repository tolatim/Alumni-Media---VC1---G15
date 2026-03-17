<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Notifications fetched successfully',
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();
        $notification = Notification::findOrFail($id);

        abort_unless(
            $notification->notifiable_type === User::class &&
            (int) $notification->notifiable_id === (int) $user->id,
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

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $notification = Notification::findOrFail($id);

        abort_unless(
            $notification->notifiable_type === User::class &&
            (int) $notification->notifiable_id === (int) $user->id,
            403
        );

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $count = Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'message' => 'Unread notification count fetched successfully',
            'data' => ['count' => $count],
        ]);
    }

    public function createNotification(User $user, string $type, string $message)
    {
        return Notification::create([
            'user_id' => $user->id,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => $type,
            'data' => json_encode(['message' => $message]),
        ]);
    }
}