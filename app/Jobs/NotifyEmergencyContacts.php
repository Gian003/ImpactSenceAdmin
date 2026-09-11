<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Services\EmergencyNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * The SMS to the rider's emergency contact and the Twilio voice call to the
 * TOC hotline, off the request thread.
 *
 * Kept as one job rather than two because the service resolves the incident's
 * place name once — a geocoding call of its own — and shares it across both
 * channels, so splitting them would mean geocoding the same crash twice. The
 * channels are still guarded individually inside the service, so a dead SMS
 * gateway does not stop the hotline ringing.
 */
class NotifyEmergencyContacts implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30];

    /**
     * If the incident is gone by the time this runs, the job goes with it
     * rather than failing loudly — there is nobody left to notify about.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(private readonly Incident $incident) {}

    public function handle(EmergencyNotificationService $notifier): void
    {
        $notifier->notifyEmergencyContact($this->incident);
    }
}
