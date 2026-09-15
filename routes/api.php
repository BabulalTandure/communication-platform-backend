<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UsernameChangeRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/profile', [AuthController::class, 'updateProfile']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin User Management Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'active', 'admin'])->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::patch('/{id}/status', [UserController::class, 'updateStatus']);
});

/*
|--------------------------------------------------------------------------
| Group Management Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'active'])->prefix('groups')->group(function () {
    // Normal User route to view joined groups
    Route::get('/my-groups', [GroupController::class, 'myGroups']);

    // Admin routes for managing groups and memberships
    Route::middleware('admin')->group(function () {
        Route::get('/', [GroupController::class, 'index']);
        Route::post('/', [GroupController::class, 'store']);
        Route::get('/{id}', [GroupController::class, 'show']);
        Route::match(['put', 'patch'], '/{id}', [GroupController::class, 'update']);
        Route::post('/{id}/members', [GroupController::class, 'addMembers']);
        Route::delete('/{id}/members/{userId}', [GroupController::class, 'removeMember']);
        Route::delete('/{id}', [GroupController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Chat & Messaging Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'active'])->prefix('chats')->group(function () {
    Route::get('/', [ChatController::class, 'index']);
    Route::post('/private', [ChatController::class, 'createPrivate']);
    Route::get('/{id}', [ChatController::class, 'show']);
    Route::get('/{id}/messages', [MessageController::class, 'index']);
    Route::post('/{id}/messages', [MessageController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Username Change Request Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'active'])->prefix('username-change-requests')->group(function () {
    // Normal user routes
    Route::post('/', [UsernameChangeRequestController::class, 'submit']);
    Route::get('/my-requests', [UsernameChangeRequestController::class, 'myRequests']);

    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::get('/', [UsernameChangeRequestController::class, 'index']);
        Route::post('/{id}/approve', [UsernameChangeRequestController::class, 'approve']);
        Route::post('/{id}/reject', [UsernameChangeRequestController::class, 'reject']);
    });
});
