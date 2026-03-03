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
            'user' => $user,
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
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'headline' => 'nullable|string',
            'phone' => 'nullable|string',
            'bio' => 'nullable|string',
            'skills' => 'nullable|string',
            'location' => 'nullable|string',
            'graduate_year' => 'nullable|int',
            'current_job' => 'nullable|string',
            'company' => 'nullable|string',
        ]);
        if($request -> hasFile('profile_photo')) {
            if($user -> profile_photo){
                Storage::disk('public') ->delete($user->profile_photo);
            }

            $path = $request->file('profile_photo')->store('cover', 'public');
            $validated['profile_photo'] = $path;
        }
        if ($request->hasFile('avatar')) {

            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store in storage/app/public/avatars
            $path = $request->file('avatar')->store('avatars', 'public');

            $validated['avatar'] = $path;
        }
        // Update user with validated data
        $user->update($validated);
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ], 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
