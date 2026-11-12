<?php

use App\Http\Controllers\Admin\AchievementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Companion\CompanionController;
use App\Http\Controllers\Guest\LandingController;
use App\Http\Controllers\Petugas\PracticeController;
use App\Http\Controllers\Petugas\QueueController;
use Illuminate\Support\Facades\Route;

// ---- Publik (landing page) -------------------------------------------------
Route::get('public/landing', [LandingController::class, 'show']);

// ---- Pendamping (tanpa login, tanpa OTP) ------------------------------------
Route::prefix('companion')->group(function () {
    // Batas longgar karena banyak pendamping bisa memakai Wi-Fi (IP) yang sama.
    Route::post('register', [CompanionController::class, 'register'])->middleware('throttle:30,1');

    Route::middleware(['companion.device', 'throttle:120,1'])->group(function () {
        Route::get('queue', [CompanionController::class, 'status']);
        Route::post('push-subscription', [CompanionController::class, 'subscribePush']);
    });
});

// ---- Petugas & admin ---------------------------------------------------------
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum,web')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::middleware('role:petugas,admin')->prefix('petugas')->group(function () {
        Route::get('practice', [PracticeController::class, 'show']);
        Route::post('practice/open', [PracticeController::class, 'open']);
        Route::post('practice/close', [PracticeController::class, 'close']);

        Route::get('queue', [QueueController::class, 'index']);
        Route::post('queue/call', [QueueController::class, 'call']);
        Route::post('queue/{id}/done', [QueueController::class, 'done'])->whereNumber('id');
        Route::post('queue/{id}/skip', [QueueController::class, 'skip'])->whereNumber('id');
        Route::post('queue/{id}/cancel', [QueueController::class, 'cancel'])->whereNumber('id');
        Route::post('queue/{id}/move', [QueueController::class, 'move'])->whereNumber('id');
        Route::delete('queue/{id}', [QueueController::class, 'destroy'])->whereNumber('id');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{user}', [UserController::class, 'update']);

        Route::get('settings', [SettingController::class, 'show']);
        Route::put('settings', [SettingController::class, 'update']);
        Route::post('doctor-photo', [SettingController::class, 'uploadDoctorPhoto']);

        Route::apiResource('achievements', AchievementController::class)->except('show');

        Route::get('reports/daily', [ReportController::class, 'daily']);
    });
});
