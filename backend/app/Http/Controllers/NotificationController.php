<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => $user->notifications()->latest('created_at')->get(),
            'unread_count' => $this->unreadCount($user->id),
            'total' => $user->notifications()->count(),
        ]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Marked as read',
            'unread_count' => $this->unreadCount($request->user()->id),
        ]);
    }

    public function destroy(Request $request, Notification $notification)
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted',
            'unread_count' => $this->unreadCount($request->user()->id),
        ]);
    }

    public function undo(Request $request, int $id)
    {
        $notification = Notification::withTrashed()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!$notification->trashed()) {
            return response()->json(['message' => 'Notification is not deleted'], 409);
        }

        $notification->restore();

        return response()->json([
            'message' => 'Notification restored',
            'unread_count' => $this->unreadCount($request->user()->id),
        ]);
    }

    private function unreadCount(int $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
