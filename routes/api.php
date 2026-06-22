<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\PatrolRegistrationController;
use App\Http\Controllers\Api\EmergencyContactController;
use App\Http\Controllers\Api\HelmetController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\PatrolAuthController;
use App\Http\Controllers\Api\RiderAuthController;
use Illuminate\Support\Facades\Route;

// ── HEALTH CHECK ──────────────────────────────────────────────────────────────
Route::get('health', fn () => response()->json(['status' => 'up']));

// ── IOT DEVICE ────────────────────────────────────────────────────────────────
// No Sanctum token — authenticated by device_code only
Route::prefix('device')->group(function () {
    Route::post('incident', [DeviceController::class, 'reportIncident']);
});

// ── RIDER ─────────────────────────────────────────────────────────────────────
Route::prefix('rider')->group(function () {

    // Public
    Route::post('register', [RiderAuthController::class, 'register']);
    Route::post('login',    [RiderAuthController::class, 'login']);

    // IoT device status push (device_code used instead of token)
    Route::post('helmet/status', [HelmetController::class, 'updateStatus']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout',    [RiderAuthController::class, 'logout']);
        Route::get('profile',    [RiderAuthController::class, 'profile']);
        Route::post('fcm-token', [RiderAuthController::class, 'updateFcmToken']);

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
    Route::post('login',           [PatrolAuthController::class, 'login']);
    Route::post('register-request',[PatrolRegistrationController::class, 'store']);
    Route::post('registration-status', [PatrolRegistrationController::class, 'status']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout',          [PatrolAuthController::class, 'logout']);
        Route::post('update-location', [PatrolAuthController::class, 'updateLocation']);
        Route::post('fcm-token',       [PatrolAuthController::class, 'updateFcmToken']);

        // Incidents
        Route::get('incidents',                          [IncidentController::class, 'patrolIndex']);
        Route::patch('incidents/{incident}/status',      [IncidentController::class, 'updateStatus']);
    });
});
