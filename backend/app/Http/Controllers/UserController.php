<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
}
