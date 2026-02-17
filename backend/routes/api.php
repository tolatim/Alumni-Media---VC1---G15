<?php

use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::apiResource('profiles', ProfileController::class);
Route::apiResource('posts', PostController::class);
Route::apiResource('likes', LikeController::class)->except(['update']);
Route::apiResource('messages', MessageController::class);
Route::apiResource('notifications', NotificationController::class);
Route::apiResource('connections', ConnectionController::class);
Route::apiResource('jobs', JobController::class);
