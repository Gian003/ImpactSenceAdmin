<?php

namespace App\Http\Controllers\Api;

use App\Events\IncidentReported;
use App\Http\Controllers\Controller;
use App\Models\Helmet;
use App\Models\Incident;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    /**
     * IoT helmet reports a crash directly.
     * Auth: device_code (no Sanctum token required).
     */
    public function reportIncident(Request $request, FcmService $fcm): JsonResponse
    {
        $data = $request->validate([
            'device_code' => ['required', 'string'],
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'type'        => ['sometimes', 'string'],
            'severity'    => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'address'     => ['nullable', 'string'],
        ]);

        $helmet = Helmet::where('device_code', $data['device_code'])
            ->with('rider')
            ->first();

        if (! $helmet) {
            return $this->apiResponse(false, 'Device not registered', null, 404);
        }

        if (! $helmet->rider_id) {
            return $this->apiResponse(false, 'Device has no paired rider', null, 422);
        }

        $incident = Incident::create([
            'rider_id'   => $helmet->rider_id,
            'helmet_id'  => $helmet->id,
            'type'       => $data['type'] ?? 'collision',
            'latitude'   => $data['latitude'],
            'longitude'  => $data['longitude'],
            'address'    => $data['address'] ?? null,
            'severity'   => $data['severity'] ?? 'high',
            'status'     => 'pending',
        ]);

        $incident->load(['rider', 'helmet']);

        // Broadcast new incident to TOC dashboard
        broadcast(new IncidentReported($incident));

        // FCM push to rider's phone — confirm the crash was detected
        if ($helmet->rider) {
            $fcm->notifyRider(
                $helmet->rider,
                'Crash Detected',
                'Your accident has been reported. Help is being contacted.',
                ['incident_id' => (string) $incident->id, 'type' => 'crash_detected']
            );
        }

        return $this->apiResponse(true, 'Incident reported', [
            'incident_id' => $incident->id,
            'status'      => $incident->status,
        ], 201);
    }
}
