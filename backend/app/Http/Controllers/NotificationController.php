<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Notification::with('user')->latest('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'string', 'max:255'],
            'source_id' => ['nullable', 'integer'],
            'message' => ['nullable', 'string'],
            'is_read' => ['nullable', 'boolean'],
        ]);

        $notification = Notification::create($validated);

        return response()->json($notification->load('user'), 201);
    }

    public function show(Notification $notification): JsonResponse
    {
        return response()->json($notification->load('user'));
    }

    public function update(Request $request, Notification $notification): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'string', 'max:255'],
            'source_id' => ['nullable', 'integer'],
            'message' => ['nullable', 'string'],
            'is_read' => ['nullable', 'boolean'],
        ]);

        $notification->update($validated);

        return response()->json($notification->load('user'));
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json(status: 204);
    }
}
