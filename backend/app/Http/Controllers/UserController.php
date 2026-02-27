<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('profile')->latest()->get();

        return response()->json([
            'message' => 'Users fetched successfully',
            'data' => $users,
        ]);
    }

    public function show($id)
    {
        $user = User::with([
            'role',
            'profile',
            'posts' => function ($query) {
                $query->latest()->withCount(['likes', 'comments']);
            },
        ])->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'message' => 'User fetched successfully',
            'data' => $user,
        ]);
    }

    public function suggestions(Request $request)
    {
        $suggestions = User::with('profile')
            ->where('id', '!=', $request->user()->id)
            ->inRandomOrder()
            ->limit(5)
            ->get();

        return response()->json([
            'message' => 'Suggestions fetched successfully',
            'data' => $suggestions,
        ]);
    }

    public function updateMyProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'headline' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:5000',
            'avatar_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'cover_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'location' => 'nullable|string|max:255',
            'graduate_year' => 'nullable|integer|min:1900|max:2100',
            'current_job' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            // Backward compatibility with old frontend field names.
            'position' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        $avatarPath = $profile?->getRawOriginal('avatar');
        $coverPath = $profile?->getRawOriginal('cover');

        if ($request->hasFile('avatar_file')) {
            $this->deleteLocalPublicFile($profile?->getRawOriginal('avatar'));
            $avatarPath = $request->file('avatar_file')->store('profiles/avatars', 'public');
        }

        if ($request->hasFile('cover_file')) {
            $this->deleteLocalPublicFile($profile?->getRawOriginal('cover'));
            $coverPath = $request->file('cover_file')->store('profiles/covers', 'public');
        }

        $user->update([
            'name' => $validated['name'],
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'headline' => $validated['headline'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'avatar' => $avatarPath,
                'cover' => $coverPath,
                'location' => $validated['location'] ?? null,
                'graduate_year' => $validated['graduate_year'] ?? ($validated['education'] ?? null),
                'current_job' => $validated['current_job'] ?? ($validated['position'] ?? null),
                'company' => $validated['company'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()->load(['role', 'profile']),
        ]);
    }

    private function deleteLocalPublicFile(?string $value): void
    {
        if (!$value) {
            return;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return;
        }

        if (Str::startsWith($value, '/storage/')) {
            $value = Str::replaceFirst('/storage/', '', $value);
        }

        if (Storage::disk('public')->exists($value)) {
            Storage::disk('public')->delete($value);
        }
    }
}
