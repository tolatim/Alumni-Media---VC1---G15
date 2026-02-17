<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Profile::with('user')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:profiles,user_id'],
            'photo' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'array'],
            'experience' => ['nullable', 'string'],
        ]);

        $profile = Profile::create($validated);

        return response()->json($profile->load('user'), 201);
    }

    public function show(Profile $profile): JsonResponse
    {
        return response()->json($profile->load('user'));
    }

    public function update(Request $request, Profile $profile): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'exists:users,id', 'unique:profiles,user_id,'.$profile->id],
            'photo' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'array'],
            'experience' => ['nullable', 'string'],
        ]);

        $profile->update($validated);

        return response()->json($profile->load('user'));
    }

    public function destroy(Profile $profile): JsonResponse
    {
        $profile->delete();

        return response()->json(status: 204);
    }
}
