<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::get();
        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'headline' => 'nullable|string',
            'phone' => 'nullable|string',
            'bio' => 'nullable|string',
            'skills' => 'nullable|string',
            'location' => 'nullable|string',
            'graduate_year' => 'nullable|string',
            'current_job' => 'nullable|string',
            'company' => 'nullable|string',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {

            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store in storage/app/public/avatars
            $path = $request->file('avatar')
                ->store('avatars', 'public');

            $validated['avatar'] = $path;
        }

        // Update user with validated data
        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
            'avatar_url' => $user->avatar
                ? env('APP_URL') . Storage::url($user->avatar)
                : null
        ], 200);
    }


    // public function updateProfile(Request $request)
    // {
    //     $user = auth()->user();

    //     if ($request->hasFile('profile_photo')) {

    //         $path = $request->file('profile_photo')
    //             ->store('profile_photos', 'public');

    //         $user->profile_photo = $path;
    //         $user->save();

    //         return response()->json([
    //             'message' => 'Uploaded',
    //             'profile_photo' => $user->profile_photo
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'No file received',
    //         'profile_photo' => null
    //     ]);
    // }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
