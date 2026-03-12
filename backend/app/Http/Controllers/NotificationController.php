<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    //
    public function index(Request $request)
    {
        return response()->json([
            'message' => 'Notifications fetched successfully',
            'data' => $request->user()->notifications()->latest()->get()->values(),
        ]);
    }


    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'success' => true,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'message' => 'Unread notifications count fetched successfully',
            'data' => [
                'count' => $count,
            ],
        ]);
    }
}
