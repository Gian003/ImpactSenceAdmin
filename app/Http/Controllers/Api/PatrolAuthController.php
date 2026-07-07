<?php

namespace App\Http\Controllers\Api;

use App\Events\PatrolLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrol\LoginRequest;
use App\Http\Requests\Patrol\UpdateLocationRequest;
use App\Models\PatrolUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PatrolAuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $patrol = PatrolUnit::where('email', $request->email)->first();

        if (! $patrol || ! Hash::check($request->password, $patrol->password)) {
            return $this->apiResponse(false, 'Invalid credentials', null, 401);
        }

        if ($request->filled('fcm_token')) {
            $patrol->update(['fcm_token' => $request->fcm_token]);
        }

        $patrol->tokens()->where('name', 'patrol-app')->delete();
        $token = $patrol->createToken('patrol-app')->plainTextToken;

        return $this->apiResponse(true, 'Login successful', [
            'patrol_unit' => $patrol,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->apiResponse(true, 'Logged out successfully');
    }

    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $patrol = $request->user();

        $patrol->update([
            'current_latitude'  => $request->latitude,
            'current_longitude' => $request->longitude,
        ]);

        // Live marker update on the TOC location-tracking map — non-fatal if
        // Pusher isn't configured, same as the other broadcast call sites.
        try {
            broadcast(new PatrolLocationUpdated($patrol));
        } catch (\Throwable $e) {
            Log::warning('Pusher broadcast failed (PatrolLocationUpdated)', ['error' => $e->getMessage()]);
        }

        return $this->apiResponse(true, 'Location updated');
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => ['required', 'string']]);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return $this->apiResponse(true, 'FCM token updated');
    }
}
