<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// ==========================================
// PROTECTED ROUTES (auth:sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // Auth endpoints
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // ==========================================
    // GROUPS ROUTES
    // ==========================================
    Route::prefix('groups')->group(function () {
        Route::get('/', [GroupController::class, 'index']);
        Route::post('/', [GroupController::class, 'store'])->middleware('role:admin,lecturer');
        Route::post('/{group}/join', [GroupController::class, 'join']);
        Route::patch('/{group}/accept-rules', [GroupController::class, 'acceptRules']);
        Route::get('/{group}', [GroupController::class, 'show']);

        // Nested topics routes
        Route::get('/{group}/topics', [TopicController::class, 'index']);
        Route::post('/{group}/topics', [TopicController::class, 'store']);
        Route::get('/{group}/topics/{topic}', [TopicController::class, 'show']);

        // Nested posts routes
        Route::get('/{group}/topics/{topic}/posts', [PostController::class, 'index']);
        Route::post('/{group}/topics/{topic}/posts', [PostController::class, 'store']);
    });

    // ==========================================
    // ROLE-BASED TEST ROUTES
    // ==========================================
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Welcome Admin!']);
        });
        Route::patch('/admin/approve-user/{userId}', [AuthController::class, 'approveUser']);
    });

    Route::middleware('role:admin,lecturer')->group(function () {
        Route::get('/lecturer/dashboard', function () {
            return response()->json(['message' => 'Welcome Lecturer!']);
        });
    });

    Route::middleware('role:admin,lecturer,student')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Welcome to your dashboard!']);
        });
    });
});