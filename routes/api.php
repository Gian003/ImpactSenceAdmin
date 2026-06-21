<?php

use App\Http\Controllers\Api\EmergencyContactController;
use App\Http\Controllers\Api\HelmetController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\PatrolAuthController;
use App\Http\Controllers\Api\RiderAuthController;
use Illuminate\Support\Facades\Route;

// ── RIDER ─────────────────────────────────────────────────────────────────────
Route::prefix('rider')->group(function () {

    // Public
    Route::post('register', [RiderAuthController::class, 'register']);
    Route::post('login',    [RiderAuthController::class, 'login']);

    // IoT device status push (device_code used instead of token)
    Route::post('helmet/status', [HelmetController::class, 'updateStatus']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout',  [RiderAuthController::class, 'logout']);
        Route::get('profile',  [RiderAuthController::class, 'profile']);

        // Helmet pairing
        Route::get('helmet',         [HelmetController::class, 'show']);
        Route::post('helmet/pair',   [HelmetController::class, 'pair']);
        Route::delete('helmet/pair', [HelmetController::class, 'unpair']);

        // Incidents
        Route::get('incidents',  [IncidentController::class, 'index']);
        Route::post('incidents', [IncidentController::class, 'store']);

        // Emergency contacts
        Route::get('emergency-contacts',          [EmergencyContactController::class, 'index']);
        Route::post('emergency-contacts',         [EmergencyContactController::class, 'store']);
        Route::delete('emergency-contacts/{emergencyContact}', [EmergencyContactController::class, 'destroy']);
    });
});

// ── PATROL ────────────────────────────────────────────────────────────────────
Route::prefix('patrol')->group(function () {

    // Public
    Route::post('login', [PatrolAuthController::class, 'login']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout',          [PatrolAuthController::class, 'logout']);
        Route::post('update-location', [PatrolAuthController::class, 'updateLocation']);

        // Incidents
        Route::get('incidents',                          [IncidentController::class, 'patrolIndex']);
        Route::patch('incidents/{incident}/status',      [IncidentController::class, 'updateStatus']);
    });
});
