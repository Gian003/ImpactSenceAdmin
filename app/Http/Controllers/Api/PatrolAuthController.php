<?php

namespace App\Http\Controllers\Api;

use App\Events\PatrolLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrol\LoginRequest;
use App\Http\Requests\Patrol\UpdateLocationRequest;
use App\Models\PatrolUnit;
use App\Support\PatrolAlertSchema;
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

        // Counts as a check-in, so the roster shows them online immediately
        // rather than leaving them offline for up to 30 seconds until the
        // location timer fires its first tick.
        $patrol->last_seen_at = now();

        if ($request->filled('fcm_token')) {
            $patrol->fcm_token = $request->fcm_token;
        }

        $patrol->save();

        $patrol->tokens()->where('name', 'patrol-app')->delete();
        $token = $patrol->createToken('patrol-app')->plainTextToken;

        return $this->apiResponse(true, 'Login successful', [
            'patrol_unit' => $patrol,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $patrol = $request->user();

        // Signing out of the app is going off shift as far as alerts are
        // concerned. A unit mid-call keeps "dispatched" so the TOC board
        // still shows who is on scene.
        $patrol->update(array_merge(
            ['status' => $patrol->status === 'dispatched' ? 'dispatched' : 'off_duty'],
            PatrolAlertSchema::ready() ? ['on_duty' => false] : [],
        ));

        $patrol->currentAccessToken()->delete();

        return $this->apiResponse(true, 'Logged out successfully');
    }

    /**
     * The officer's own on/off-duty switch. Only an on-duty unit can be
     * alerted as the nearest to a crash, so this is what stops an officer who
     * has gone home, but left the app open, from being called out.
     */
    public function updateDuty(Request $request): JsonResponse
    {
        $data   = $request->validate(['on_duty' => ['required', 'boolean']]);
        $patrol = $request->user();

        if (! PatrolAlertSchema::ready()) {
            return $this->apiResponse(false,
                'Duty status is not available yet. Ask the TOC to update the server.', null, 503);
        }

        $patrol->on_duty = (bool) $data['on_duty'];

        // A unit mid-call stays "dispatched" either way, and drops back to the
        // matching idle status when the call is closed.
        if ($patrol->status !== 'dispatched') {
            $patrol->status = $patrol->idleStatus();
        }

        $patrol->save();

        try {
            broadcast(new PatrolLocationUpdated($patrol));
        } catch (\Throwable $e) {
            Log::warning('Pusher broadcast failed (PatrolLocationUpdated/duty)', ['error' => $e->getMessage()]);
        }

        return $this->apiResponse(true, $patrol->on_duty
            ? 'On duty. You can be alerted to nearby crashes.'
            : 'Off duty. You will not be alerted to crashes.', $this->dutyState($patrol));
    }

    /** @return array{on_duty: bool, status: string} */
    private function dutyState(PatrolUnit $patrol): array
    {
        return ['on_duty' => (bool) $patrol->on_duty, 'status' => (string) $patrol->status];
    }

    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $patrol = $request->user();

        // last_seen_at is what makes the coordinates meaningful: without it a
        // fix pushed 30 seconds ago and one left over from a phone that has
        // been off for a week look identical on the TOC roster.
        $patrol->update([
            'current_latitude'  => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_seen_at'      => now(),
        ]);

        // Live marker update on the TOC location-tracking map — non-fatal if
        // Pusher isn't configured, same as the other broadcast call sites.
        try {
            broadcast(new PatrolLocationUpdated($patrol));
        } catch (\Throwable $e) {
            Log::warning('Pusher broadcast failed (PatrolLocationUpdated)', ['error' => $e->getMessage()]);
        }

        // The duty state rides back on every check-in, so the app's switch
        // corrects itself within one tick if it was changed elsewhere — by
        // the TOC deactivating the officer, or by the officer on another phone.
        return $this->apiResponse(true, 'Location updated', $this->dutyState($patrol));
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => ['nullable', 'string']]);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return $this->apiResponse(true, $request->fcm_token === null
            ? 'Push notifications disabled'
            : 'FCM token updated');
    }
}
