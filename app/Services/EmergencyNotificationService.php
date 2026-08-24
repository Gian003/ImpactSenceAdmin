<?php

namespace App\Services;

use App\Models\Incident;

class EmergencyNotificationService
{
    public function __construct(
        private SmsService $sms,
        private VoiceCallService $voiceCall,
    ) {}

    // Notifies the rider's emergency contact via SMS (Semaphore, PH-local rates)
    // and a spoken voice call (Twilio TTS) - same message on both channels,
    // same text format the device's own SIM800L SMS already uses, so the
    // message is consistent regardless of which path actually delivered it.
    // Both are non-fatal by design (see SmsService/VoiceCallService) so a
    // notification failure never breaks the incident report that triggered it.
    public function notifyEmergencyContact(Incident $incident): void
    {
        $incident->loadMissing('rider.emergencyContacts');

        $contact = $incident->rider?->emergencyContacts->first();

        if (! $contact) {
            return;
        }

        $message = $this->buildMessage($incident);

        $this->sms->send($contact->phone_number, $message);
        $this->voiceCall->call($contact->phone_number, $message);
    }

    private function buildMessage(Incident $incident): string
    {
        $riderName = $incident->rider?->full_name ?? 'A registered ImpactSense rider';
        $mapsLink  = "https://maps.google.com/?q={$incident->latitude},{$incident->longitude}";

        return "{$riderName} may have been in a motorcycle accident. Severity: {$incident->severity}. Location: {$mapsLink}";
    }
}
