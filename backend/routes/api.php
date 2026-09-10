<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\BatchInstructorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Public catalog browsing — guests see published content only; an
    // authenticated admin (if a valid token is sent) also sees drafts.
    // Not wrapped in auth:sanctum since guests must be allowed through.
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::get('/courses/{course}/curriculum', [CourseController::class, 'curriculum']);
    // Also public (not wrapped in auth:sanctum): preview lessons must be
    // readable by guests. LessonPolicy::viewContent enforces preview-or-admin.
    Route::get('/lessons/{lesson}/content', [LessonController::class, 'content']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{course}', [CourseController::class, 'update']);
        Route::patch('/courses/{course}/status', [CourseController::class, 'updateStatus']);
        Route::delete('/courses/{course}', [CourseController::class, 'destroy']);

        Route::post('/courses/{course}/modules', [ModuleController::class, 'store']);
        Route::post('/courses/{course}/modules/reorder', [ModuleController::class, 'reorder']);
        Route::put('/modules/{module}', [ModuleController::class, 'update']);
        Route::delete('/modules/{module}', [ModuleController::class, 'destroy']);

        Route::post('/modules/{module}/sections', [SectionController::class, 'store']);
        Route::post('/modules/{module}/sections/reorder', [SectionController::class, 'reorder']);
        Route::put('/sections/{section}', [SectionController::class, 'update']);
        Route::delete('/sections/{section}', [SectionController::class, 'destroy']);

        Route::post('/sections/{section}/lessons', [LessonController::class, 'store']);
        Route::post('/sections/{section}/lessons/reorder', [LessonController::class, 'reorder']);
        Route::put('/lessons/{lesson}', [LessonController::class, 'update']);
        Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy']);

        Route::post('/media', [MediaController::class, 'store']);

        Route::get('/batches', [BatchController::class, 'index']);
        Route::get('/batches/{batch}', [BatchController::class, 'show']);
        Route::post('/courses/{course}/batches', [BatchController::class, 'store']);
        Route::put('/batches/{batch}', [BatchController::class, 'update']);
        Route::delete('/batches/{batch}', [BatchController::class, 'destroy']);
        Route::post('/batches/{batch}/instructors', [BatchInstructorController::class, 'store']);
        Route::delete('/batches/{batch}/instructors/{user}', [BatchInstructorController::class, 'destroy']);
    });
});
