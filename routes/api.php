<?php

use App\Http\Controllers\Api\PatrolAuthController;
use App\Http\Controllers\Api\RiderAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('rider')->group(function () {
    Route::post('register', [RiderAuthController::class, 'register']);
    Route::post('login', [RiderAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [RiderAuthController::class, 'logout']);
        Route::get('profile', [RiderAuthController::class, 'profile']);
    });
});

Route::prefix('patrol')->group(function () {
    Route::post('login', [PatrolAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [PatrolAuthController::class, 'logout']);
        Route::post('update-location', [PatrolAuthController::class, 'updateLocation']);
    });
});
