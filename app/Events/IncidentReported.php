<?php

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentReported implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Incident $incident) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('incidents'),           // TOC dashboard listens here
        ];
    }

    public function broadcastAs(): string
    {
        return 'incident.reported';
    }

    public function broadcastWith(): array
    {
        return [
            'id'        => $this->incident->id,
            'type'      => $this->incident->type,
            'severity'  => $this->incident->severity,
            'status'    => $this->incident->status,
            'latitude'  => $this->incident->latitude,
            'longitude' => $this->incident->longitude,
            'address'   => $this->incident->address,
            'rider'     => [
                'id'           => $this->incident->rider?->id,
                'full_name'    => $this->incident->rider?->full_name,
                'phone_number' => $this->incident->rider?->phone_number,
            ],
            'reported_at' => $this->incident->created_at->toISOString(),
        ];
    }
}
