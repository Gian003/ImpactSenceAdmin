<?php

namespace App\Events;

use App\Models\PatrolRegistration;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatrolRegistrationSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly PatrolRegistration $registration) {}

    public function broadcastOn(): array
    {
        // TOC dashboard listens on the same 'incidents' channel
        // plus a dedicated registrations channel
        return [new Channel('patrol-registrations')];
    }

    public function broadcastAs(): string
    {
        return 'registration.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->registration->id,
            'full_name'    => $this->registration->full_name,
            'email'        => $this->registration->email,
            'phone_number' => $this->registration->phone_number,
            'submitted_at' => $this->registration->created_at->toISOString(),
        ];
    }
}
