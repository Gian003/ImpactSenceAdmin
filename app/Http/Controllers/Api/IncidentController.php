<?php

namespace App\Http\Controllers\Api;

use App\Events\IncidentReported;
use App\Events\IncidentStatusUpdated;
use App\Events\PatrolDispatched;
use App\Events\PatrolLocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IncidentController extends Controller
{
    // Rider phone app: report a crash detected by the helmet via Bluetooth
    public function store(Request $request, FcmService $fcm): JsonResponse
    {
        $data = $request->validate([
            'type'      => ['sometimes', 'string'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address'   => ['nullable', 'string'],
            'severity'  => ['sometimes', Rule::in(['low', 'medium', 'high', 'critical'])],
        ]);

        $rider  = $request->user();
        $helmet = $rider->helmet;

        $incident = Incident::create([
            ...$data,
            'rider_id'  => $rider->id,
            'helmet_id' => $helmet?->id,
            'status'    => 'pending',
        ]);

        $incident->load(['rider', 'helmet']);

        // Broadcast to TOC dashboard — non-fatal if Pusher is not configured
        try {
            broadcast(new IncidentReported($incident))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Pusher broadcast failed (IncidentReported)', ['error' => $e->getMessage()]);
        }

        // FCM push to rider — non-fatal if Firebase is not configured
        $fcm->notifyRider(
            $rider,
            'Crash Reported',
            'Your incident has been reported. TOC has been alerted.',
            ['incident_id' => (string) $incident->id, 'type' => 'crash_confirmed']
        );

        return $this->apiResponse(true, 'Incident reported', $incident, 201);
    }

    // Rider: list own incidents
    public function index(Request $request): JsonResponse
    {
        $incidents = $request->user()
            ->incidents()
            ->with('patrolUnit:id,full_name,badge_number')
            ->latest()
            ->get();

        return $this->apiResponse(true, 'Incidents retrieved', $incidents);
    }

    // Patrol: list incidents assigned to this unit (or all pending)
    public function patrolIndex(Request $request): JsonResponse
    {
        $incidents = Incident::query()
            ->where(function ($q) use ($request) {
                $q->where('patrol_unit_id', $request->user()->id)
                  ->orWhere('status', 'pending');
            })
            ->with('rider:id,full_name,phone_number', 'helmet:id,device_code,model')
            ->latest()
            ->get();

        return $this->apiResponse(true, 'Incidents retrieved', $incidents);
    }

    // Patrol: update incident status (dispatched / resolved / false_alarm)
    public function updateStatus(Request $request, Incident $incident, FcmService $fcm): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['dispatched', 'resolved', 'false_alarm'])],
            'notes'  => ['nullable', 'string'],
        ]);

        $patrol  = $request->user();
        $updates = ['status' => $data['status']];

        if ($data['status'] === 'dispatched' && $incident->patrol_unit_id === null) {
            $updates['patrol_unit_id'] = $patrol->id;
            $updates['dispatched_at']  = now();
        }

        if ($data['status'] === 'resolved') {
            $updates['resolved_at'] = now();
        }

        if (isset($data['notes'])) {
            $updates['notes'] = $data['notes'];
        }

        $incident->update($updates);
        $incident->load(['rider', 'patrolUnit']);

        // Keep the patrol unit's own status in sync with the incident they're
        // responding to — this previously never changed after being set to
        // "off_duty" at registration approval, so Stand By / In Action (and
        // the TOC map marker color) never reflected reality.
        if ($data['status'] === 'dispatched') {
            $patrol->update(['status' => 'dispatched']);
        } elseif (in_array($data['status'], ['resolved', 'false_alarm'], true)) {
            $patrol->update(['status' => 'off_duty']);
        }

        try {
            broadcast(new PatrolLocationUpdated($patrol));
        } catch (\Throwable $e) {
            Log::warning('Pusher broadcast failed (PatrolLocationUpdated)', ['error' => $e->getMessage()]);
        }

        // Broadcast dispatch event to the assigned patrol unit — non-fatal if Pusher not configured
        if ($data['status'] === 'dispatched') {
            try {
                broadcast(new PatrolDispatched($incident));
            } catch (\Throwable $e) {
                Log::warning('Pusher broadcast failed (PatrolDispatched)', ['error' => $e->getMessage()]);
            }

            // FCM push to patrol app
            if ($incident->patrolUnit) {
                $fcm->notifyPatrol(
                    $incident->patrolUnit,
                    'Dispatch Alert',
                    "Respond to {$incident->type} at {$incident->address}",
                    ['incident_id' => (string) $incident->id, 'type' => 'dispatch']
                );
            }
        }

        // FCM push to rider when their incident is resolved
        if ($data['status'] === 'resolved' && $incident->rider) {
            $fcm->notifyRider(
                $incident->rider,
                'Incident Resolved',
                'Help has arrived. Your incident has been marked resolved.',
                ['incident_id' => (string) $incident->id, 'type' => 'resolved']
            );
        }

        // Always broadcast status change back to TOC dashboard — non-fatal
        try {
            broadcast(new IncidentStatusUpdated($incident));
        } catch (\Throwable $e) {
            Log::warning('Pusher broadcast failed (IncidentStatusUpdated)', ['error' => $e->getMessage()]);
        }

        return $this->apiResponse(true, 'Status updated', $incident->fresh());
    }
}
