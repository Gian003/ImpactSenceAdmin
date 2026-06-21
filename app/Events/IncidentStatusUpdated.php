<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Incident $incident) {}

    public function broadcastOn(): array
    {
        // Same public channel the TOC dashboard already subscribes to
        return [new Channel('incidents')];
    }

    public function broadcastAs(): string
    {
        return 'incident.status_updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->incident->id,
            'status'         => $this->incident->status,
            'severity'       => $this->incident->severity,
            'patrol_unit'    => $this->incident->patrolUnit ? [
                'id'           => $this->incident->patrolUnit->id,
                'full_name'    => $this->incident->patrolUnit->full_name,
                'badge_number' => $this->incident->patrolUnit->badge_number,
            ] : null,
            'dispatched_at'  => $this->incident->dispatched_at?->toISOString(),
            'resolved_at'    => $this->incident->resolved_at?->toISOString(),
            'notes'          => $this->incident->notes,
        ];
    }
}
