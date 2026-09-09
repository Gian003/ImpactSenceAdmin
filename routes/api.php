<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\PatrolRegistrationController;
use App\Http\Controllers\Api\EmergencyContactController;
use App\Http\Controllers\Api\RiderDeviceController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\PatrolAuthController;
use App\Http\Controllers\Api\RiderAuthController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// ── HEALTH CHECK ──────────────────────────────────────────────────────────────
Route::get('health', fn() => response()->json(['status' => 'up']));

// ── BROADCASTING AUTH (mobile apps, Sanctum bearer token) ────────────────────
// Registers POST /api/broadcasting/auth (this file is already loaded under
// the /api prefix) for private-channel subscriptions (e.g. patrol.{id}) from
// the Flutter apps, which authenticate via bearer token rather than the
// session cookie the default /broadcasting/auth route expects.
Broadcast::routes([
    'middleware' => ['auth:sanctum'],
]);

// ── IOT DEVICE ────────────────────────────────────────────────────────────────
// No Sanctum token — authenticated by device_code only
Route::prefix('device')->group(function () {
    Route::post('incident', [DeviceController::class, 'reportIncident']);
    Route::get('emergency-contact', [DeviceController::class, 'getEmergencyContact']);
});

// ── RIDER ─────────────────────────────────────────────────────────────────────
Route::prefix('rider')->group(function () {

    // Public
    Route::post('register', [RiderAuthController::class, 'register']);
    Route::post('login',    [RiderAuthController::class, 'login']);

    // OTP — send a 6-digit code to the given email, verify it before registration
    Route::post('otp/send',   [RiderAuthController::class, 'otpSend']);
    Route::post('otp/verify', [RiderAuthController::class, 'otpVerify']);

    // IoT device status push (device_code used instead of token)
    Route::post('device/status', [RiderDeviceController::class, 'updateStatus']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout',    [RiderAuthController::class, 'logout']);
        Route::get('profile',    [RiderAuthController::class, 'profile']);
        Route::patch('profile',          [RiderAuthController::class, 'updateProfile']);
        Route::post('change-password',   [RiderAuthController::class, 'changePassword']);
        Route::post('fcm-token',         [RiderAuthController::class, 'updateFcmToken']);

        // Device pairing
        Route::get('device', [RiderDeviceController::class, 'show']);

        // Rate-limited: pairing_key is the real secret gating this, but a slow
        // brute-force is still worth blocking outright rather than relying on
        // key entropy alone.
        Route::post('device/pair', [RiderDeviceController::class, 'pair'])
            ->middleware('throttle:5,1');

        Route::delete('device/pair', [RiderDeviceController::class, 'unpair']);

        // Incidents
        Route::get('incidents',                          [IncidentController::class, 'index']);
        Route::post('incidents',                         [IncidentController::class, 'store']);
        Route::patch('incidents/{incident}/cancel',      [IncidentController::class, 'cancelIncident']);

        // Emergency contacts
        Route::get('emergency-contacts',          [EmergencyContactController::class, 'index']);
        Route::post('emergency-contacts',         [EmergencyContactController::class, 'store']);
        Route::delete('emergency-contacts/{emergencyContact}', [EmergencyContactController::class, 'destroy']);
    });
});

// ── PATROL ────────────────────────────────────────────────────────────────────
Route::prefix('patrol')->group(function () {

    // Public — the only three patrol endpoints reachable without a token, so
    // each carries its own rate limit (same approach as the rider device-pair
    // route). login is a credential brute-force surface; register-request
    // writes a row and stores an uploaded photo; registration-status answers
    // whether a given email has a registration at all, which is an account
    // enumeration oracle if left uncapped. status gets a looser cap because
    // the app's "Check Status" button is tapped by hand.
    Route::post('login',           [PatrolAuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('register-request', [PatrolRegistrationController::class, 'store'])
        ->middleware('throttle:5,1');
    Route::post('registration-status', [PatrolRegistrationController::class, 'status'])
        ->middleware('throttle:10,1');

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
