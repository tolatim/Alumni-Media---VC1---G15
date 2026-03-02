<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();

        return response()->json([
            'message' => 'Users fetched successfully',
            'data' => $users,
        ]);
    }

    public function show($id)
    {
        $query = User::query()->with(['role']);

        if (Schema::hasTable('posts')) {
            $query->with([
                'posts' => function ($postQuery) {
                    $postQuery->latest();

                    $countableRelations = [];
                    if (Schema::hasTable('likes')) {
                        $countableRelations[] = 'likes';
                    }
                    if (Schema::hasTable('comments')) {
                        $countableRelations[] = 'comments';
                    }

                    if (!empty($countableRelations)) {
                        $postQuery->withCount($countableRelations);
                    }
                },
            ]);
        }

        $user = $query->find($id);

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
        $suggestions = User::query()
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
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:255',
            'headline' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:5000',
            'skills' => 'nullable|string|max:2000',
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
        $avatarPath = $user->getRawOriginal('avatar');
        $coverPath = $user->getRawOriginal('cover');

        if ($request->hasFile('avatar_file')) {
            $this->deleteLocalPublicFile($user->getRawOriginal('avatar'));
            $avatarPath = $request->file('avatar_file')->store('profiles/avatars', 'public');
        }

        if ($request->hasFile('cover_file')) {
            $this->deleteLocalPublicFile($user->getRawOriginal('cover'));
            $coverPath = $request->file('cover_file')->store('profiles/covers', 'public');
        }

        $firstName = $validated['first_name'] ?? null;
        $lastName = $validated['last_name'] ?? null;

        if ((!$firstName || !$lastName) && !empty($validated['name'])) {
            $parts = preg_split('/\s+/', trim($validated['name']));
            $firstName = $firstName ?: ($parts[0] ?? '');
            $lastName = $lastName ?: (count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '');
        }

        if (!$firstName || !$lastName) {
            return response()->json([
                'message' => 'First name and last name are required.',
            ], 422);
        }

        $user->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        $user->update([
            'headline' => $validated['headline'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'avatar' => $avatarPath,
            'cover' => $coverPath,
            'location' => $validated['location'] ?? null,
            'graduate_year' => $validated['graduate_year'] ?? ($validated['education'] ?? null),
            'current_job' => $validated['current_job'] ?? ($validated['position'] ?? null),
            'company' => $validated['company'] ?? null,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()->load(['role']),
        ]);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed|different:old_password',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['old_password'], $user->password)) {
            return response()->json([
                'message' => 'Old password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => $validated['new_password'],
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
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

