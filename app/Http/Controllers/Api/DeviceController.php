<?php

namespace App\Http\Controllers\Api;

use App\Events\IncidentReported;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
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

        // Broadcast new incident to TOC dashboard — non-fatal if Pusher not configured
        try {
            broadcast(new IncidentReported($incident));
        } catch (\Throwable $e) {
            Log::warning('Pusher broadcast failed (IncidentReported/Device)', ['error' => $e->getMessage()]);
        }

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

    /**
     * IoT device fetches the paired rider's emergency contact to cache locally,
     * so an SMS can still be sent from the device even if the backend is
     * unreachable at the moment of an actual crash (SIM800L SMS works on a much
     * weaker signal than the data connection this HTTP call itself needs).
     * Auth: device_code (no Sanctum token required).
     */
    public function getEmergencyContact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_code' => ['required', 'string'],
        ]);

        $helmet = Helmet::where('device_code', $data['device_code'])
            ->with('rider.emergencyContacts')
            ->first();

        if (! $helmet) {
            return $this->apiResponse(false, 'Device not registered', null, 404);
        }

        if (! $helmet->rider_id) {
            return $this->apiResponse(false, 'Device has no paired rider', null, 422);
        }

        $contact = $helmet->rider->emergencyContacts->first();

        if (! $contact) {
            return $this->apiResponse(false, 'Rider has no emergency contact on file', null, 404);
        }

        return $this->apiResponse(true, 'Emergency contact retrieved', [
            'rider_name'   => $helmet->rider->full_name,
            'name'         => $contact->name,
            'phone_number' => $contact->phone_number,
        ]);
    }
}
