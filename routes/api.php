<?php

use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

// Protected routes (require Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    // Post routes
    Route::post('/upload', [App\Http\Controllers\Api\UploadController::class, 'upload']);
    Route::get('/posts', [App\Http\Controllers\Api\UploadController::class, 'index']);
    Route::get('/posts/{id}', [App\Http\Controllers\Api\UploadController::class, 'show']);
    Route::put('/posts/{id}', [App\Http\Controllers\Api\UploadController::class, 'update']);
    Route::delete('/posts/{id}', [App\Http\Controllers\Api\UploadController::class, 'destroy']);
    
    // Like routes
    Route::post('/posts/{postId}/like', [App\Http\Controllers\Api\LikeController::class, 'toggle']);
    Route::get('/posts/{postId}/likes', [App\Http\Controllers\Api\LikeController::class, 'index']);
    
    // Comment routes
    Route::post('/posts/{postId}/comments', [App\Http\Controllers\Api\CommentController::class, 'store']);
    Route::get('/posts/{postId}/comments', [App\Http\Controllers\Api\CommentController::class, 'index']);
    Route::put('/posts/{postId}/comments/{commentId}', [App\Http\Controllers\Api\CommentController::class, 'update']);
    Route::delete('/posts/{postId}/comments/{commentId}', [App\Http\Controllers\Api\CommentController::class, 'destroy']);
    
    // Auth routes
    Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me']);
    Route::put('/me', [App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::get('/users/{userId}', [App\Http\Controllers\Api\AuthController::class, 'profile']);
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
});

