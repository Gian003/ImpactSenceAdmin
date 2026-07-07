<?php

namespace App\Events;

use App\Models\PatrolUnit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatrolLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly PatrolUnit $patrolUnit) {}

    public function broadcastOn(): array
    {
        // Public channel — only ever consumed by the already-authenticated
        // TOC dashboard, same trust model as the 'incidents' channel.
        return [
            new Channel('patrol-locations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'patrol.location_updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'                => $this->patrolUnit->id,
            'full_name'         => $this->patrolUnit->full_name,
            'badge_number'      => $this->patrolUnit->badge_number,
            'status'            => $this->patrolUnit->status,
            'current_latitude'  => $this->patrolUnit->current_latitude,
            'current_longitude' => $this->patrolUnit->current_longitude,
        ];
    }
}
