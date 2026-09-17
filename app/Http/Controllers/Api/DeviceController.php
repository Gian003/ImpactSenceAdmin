<?php

namespace App\Http\Controllers\Api;

use App\Events\IncidentReported;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Device;
use App\Jobs\AlertNearestPatrol;
use App\Jobs\NotifyEmergencyContacts;
use App\Support\PatrolAlertSchema;
use App\Jobs\SendPushNotification;
use App\Models\Incident;
use App\Services\EmergencyNotificationService;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    /**
     * IoT device reports a crash directly.
     * Auth: device_code (no Sanctum token required).
     */
    /**
     * @param bool $notifyRiderAndContacts
     *        Whether to push to the rider and call/text their emergency
     *        contacts. Always true for a real device: the router injects only
     *        the three typed dependencies above, so nothing arriving over HTTP
     *        can turn this off. The TOC's demonstration tool calls this method
     *        directly with false, so a drill can light up the dispatch board
     *        without texting somebody's mother or spending SMS credits.
     */
    public function reportIncident(
        Request $request,
        FcmService $fcm,
        EmergencyNotificationService $emergencyNotifier,
        bool $notifyRiderAndContacts = true
    ): JsonResponse
    {
        $data = $request->validate([
            'device_code' => ['required', 'string'],
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'type'        => ['sometimes', 'string'],
            'severity'    => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
            'address'     => ['nullable', 'string'],
            // Whether the coordinates are a real GPS fix. Firmware without a
            // fix reports its fallback point, which is not where the rider is.
            // Absent from older firmware, and stored as unknown when it is.
            'location_verified' => ['sometimes', 'boolean'],
        ]);

        $device = Device::where('device_code', $data['device_code'])
            ->with('rider')
            ->first();

        if (! $device) {
            return $this->apiResponse(false, 'Device not registered', null, 404);
        }

        if (! $device->rider_id) {
            return $this->apiResponse(false, 'Device has no paired rider', null, 422);
        }

        $attributes = [
            'rider_id'   => $device->rider_id,
            'device_id'  => $device->id,
            'type'       => $data['type'] ?? 'collision',
            'latitude'   => $data['latitude'],
            'longitude'  => $data['longitude'],
            'address'    => $data['address'] ?? null,
            'severity'   => $data['severity'] ?? 'high',
            'status'     => 'pending',
        ];

        // Only once the column exists. This is the one insert that must never
        // fail, and new code can reach the server before its migration does.
        if (PatrolAlertSchema::ready()) {
            $attributes['location_verified'] = array_key_exists('location_verified', $data)
                ? (bool) $data['location_verified']
                : null;
        }

        $incident = Incident::create($attributes);

        $incident->load(['rider', 'device']);

        \App\Models\IncidentEvent::record(
            $incident,
            \App\Models\IncidentEvent::REPORTED,
            [
                'status_to' => 'pending',
                'payload'   => array_filter([
                    'source'      => 'device',
                    'device_code' => $device->device_code,
                    'severity'    => $incident->severity,
                    'address'     => $incident->address,
                ]),
            ],
        );

        // Broadcast new incident to TOC dashboard — non-fatal if Pusher not configured
        try {
            broadcast(new IncidentReported($incident));
        } catch (\Throwable $e) {
            Log::warning('Pusher broadcast failed (IncidentReported/Device)', ['error' => $e->getMessage()]);
        }

        // Queued, not called. This endpoint is a device that has just detected
        // an impact, often on a poor GSM link: it should be told its report is
        // saved as soon as it is saved, not after Google, Semaphore and Twilio
        // have each been waited on in turn.
        //
        // On the sync connection these still run inline exactly as before, so
        // nothing changes until a worker is actually draining the queue.
        // Dispatching is itself guarded. On the sync connection a queued job
        // runs inline and rethrows into this request, so an unexpected throw
        // anywhere in the notification path would once again kill a crash
        // report that is already saved; on a real connection, dispatch() can
        // throw if the queue backend is unreachable. Neither is a reason to
        // tell a device its crash was not recorded.
        if ($notifyRiderAndContacts) {
            try {
                if ($device->rider?->fcm_token) {
                    SendPushNotification::dispatch(
                        $device->rider->fcm_token,
                        'Crash Detected',
                        'Your accident has been reported. Help is being contacted.',
                        ['incident_id' => (string) $incident->id, 'type' => 'crash_detected'],
                    );
                }

                // Two different recipients, not one: Semaphore SMS goes to the
                // rider's emergency contact, while the Twilio TTS call goes to
                // the TOC hotline (see EmergencyNotificationService).
                NotifyEmergencyContacts::dispatch($incident);
            } catch (\Throwable $e) {
                Log::error('Queueing crash notifications failed', [
                    'incident' => $incident->id, 'error' => $e->getMessage(),
                ]);
            }

            // The nearest free patrol unit, at the same time as the TOC. Its
            // own guard, so a problem here cannot touch the notifications
            // above, and off unless enabled (see config/services.php).
            if (config('services.patrol_alert.enabled')) {
                try {
                    AlertNearestPatrol::start($incident);
                } catch (\Throwable $e) {
                    Log::error('Queueing nearest-patrol alert failed', [
                        'incident' => $incident->id, 'error' => $e->getMessage(),
                    ]);
                }
            }
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

        $device = Device::where('device_code', $data['device_code'])
            ->with('rider.emergencyContacts')
            ->first();

        if (! $device) {
            return $this->apiResponse(false, 'Device not registered', null, 404);
        }

        if (! $device->rider_id) {
            return $this->apiResponse(false, 'Device has no paired rider', null, 422);
        }

        $contact = $device->rider->emergencyContacts->first();

        if (! $contact) {
            return $this->apiResponse(false, 'Rider has no emergency contact on file', null, 404);
        }

        return $this->apiResponse(true, 'Emergency contact retrieved', [
            'rider_name'       => $device->rider->full_name,
            'name'             => $contact->name,
            'phone_number'     => $contact->phone_number,
            'sim_phone_number' => $device->sim_phone_number,
        ]);
    }
}
