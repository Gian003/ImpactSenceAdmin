<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatrolDispatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Incident $incident) {}

    public function broadcastOn(): array
    {
        // Public channel scoped to this patrol unit's ID.
        // Switch to PrivateChannel + auth endpoint before production.
        return [
            new Channel('patrol.' . $this->incident->patrol_unit_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'patrol.dispatched';
    }

    public function broadcastWith(): array
    {
        return [
            'incident_id'  => $this->incident->id,
            'type'         => $this->incident->type,
            'severity'     => $this->incident->severity,
            'latitude'     => $this->incident->latitude,
            'longitude'    => $this->incident->longitude,
            'address'      => $this->incident->address,
            'rider'        => [
                'full_name'    => $this->incident->rider?->full_name,
                'phone_number' => $this->incident->rider?->phone_number,
            ],
            'toc_operator' => 'TOC Operator',
            'dispatched_at' => $this->incident->dispatched_at?->toISOString(),
        ];
    }
}
