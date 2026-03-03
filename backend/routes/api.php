<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::apiResource('/users', UserController::class);
Route::get('/users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->patch('/user/profile', [UserController::class, 'update']);
