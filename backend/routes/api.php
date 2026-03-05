<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::apiResource('/users', UserController::class);
Route::get('/users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->patch('/user/profile', [UserController::class, 'update']);
Route::apiResource('/posts', PostController::class);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    // Route::post('/posts', [PostController::class, 'update']);
    // Route::delete('/posts{id}', [PostController::class, 'destroy']);

   
});
