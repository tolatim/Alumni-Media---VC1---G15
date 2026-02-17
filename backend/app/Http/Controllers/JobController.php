<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Job::with('postedBy')->latest('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric'],
            'posted_by' => ['nullable', 'exists:users,id'],
        ]);

        $job = Job::create($validated);

        return response()->json($job->load('postedBy'), 201);
    }

    public function show(Job $job): JsonResponse
    {
        return response()->json($job->load('postedBy'));
    }

    public function update(Request $request, Job $job): JsonResponse
    {
        $validated = $request->validate([
            'company' => ['sometimes', 'string', 'max:255'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric'],
            'posted_by' => ['nullable', 'exists:users,id'],
        ]);

        $job->update($validated);

        return response()->json($job->load('postedBy'));
    }

    public function destroy(Job $job): JsonResponse
    {
        $job->delete();

        return response()->json(status: 204);
    }
}
