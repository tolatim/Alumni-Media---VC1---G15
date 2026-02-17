<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConnectionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Connection::with(['requester', 'receiver'])->latest('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requester_id' => ['required', 'exists:users,id', 'different:receiver_id'],
            'receiver_id' => ['required', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'rejected'])],
        ]);

        $connection = Connection::create($validated);

        return response()->json($connection->load(['requester', 'receiver']), 201);
    }

    public function show(Connection $connection): JsonResponse
    {
        return response()->json($connection->load(['requester', 'receiver']));
    }

    public function update(Request $request, Connection $connection): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'accepted', 'rejected'])],
        ]);

        $connection->update($validated);

        return response()->json($connection->load(['requester', 'receiver']));
    }

    public function destroy(Connection $connection): JsonResponse
    {
        $connection->delete();

        return response()->json(status: 204);
    }
}
