<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 20), 1), 50);

        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'Notifications fetched successfully',
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::query()
            ->where('user_id', $request->user()->id)
            ->where('seen', false)
            ->count();

        return response()->json([
            'message' => 'Unread notification count fetched successfully',
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    public function markSeen(Request $request, Notification $notification)
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (!$notification->seen) {
            $notification->update(['seen' => true]);
        }

        return response()->json([
            'message' => 'Notification marked as seen',
            'data' => $notification->fresh(),
        ]);
    }

    public function markAllSeen(Request $request)
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->where('seen', false)
            ->update(['seen' => true]);

        return response()->json([
            'message' => 'All notifications marked as seen',
        ]);
    }
}
