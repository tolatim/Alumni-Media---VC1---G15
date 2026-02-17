<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Message::with(['sender', 'receiver'])->latest('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sender_id' => ['required', 'exists:users,id'],
            'receiver_id' => ['required', 'exists:users,id', 'different:sender_id'],
            'content' => ['required', 'string'],
            'read_at' => ['nullable', 'date'],
        ]);

        $message = Message::create($validated);

        return response()->json($message->load(['sender', 'receiver']), 201);
    }

    public function show(Message $message): JsonResponse
    {
        return response()->json($message->load(['sender', 'receiver']));
    }

    public function update(Request $request, Message $message): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['sometimes', 'string'],
            'read_at' => ['nullable', 'date'],
        ]);

        $message->update($validated);

        return response()->json($message->load(['sender', 'receiver']));
    }

    public function destroy(Message $message): JsonResponse
    {
        $message->delete();

        return response()->json(status: 204);
    }
}
