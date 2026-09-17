<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * "You are the nearest unit" — sent on the same private channel as a
 * dispatch, under its own name so the app can word it as a request rather
 * than an assignment. The payload matches PatrolDispatched so the app parses
 * both the same way; `status` stays "pending" because nobody has the call yet.
 */
class PatrolNearestAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Incident $incident,
        public readonly int $patrolUnitId,
        public readonly float $distanceKm,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('patrol.' . $this->patrolUnitId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'patrol.nearest_alert';
    }

    public function broadcastWith(): array
    {
        return [
            'incident_id' => $this->incident->id,
            'type'        => $this->incident->type,
            'severity'    => $this->incident->severity,
            'status'      => 'pending',
            'latitude'    => $this->incident->latitude,
            'longitude'   => $this->incident->longitude,
            'address'     => $this->incident->address,
            'distance_km' => $this->distanceKm,
            'rider'       => [
                'full_name'    => $this->incident->rider?->full_name,
                'phone_number' => $this->incident->rider?->phone_number,
            ],
        ];
    }
}
