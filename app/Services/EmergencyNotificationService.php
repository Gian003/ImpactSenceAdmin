<?php

namespace App\Services;

use App\Models\Incident;

class EmergencyNotificationService
{
    public function __construct(
        private SmsService $sms,
        private VoiceCallService $voiceCall,
    ) {}

    // Two-channel emergency notification:
    //
    //   SMS (Semaphore) → rider's emergency contact (family/friend)
    //     Delivers the crash details as a text message they can read and
    //     share, including the Google Maps link to the exact location.
    //
    //   Voice call (Twilio TTS) → TOC hotline
    //     Dispatches an AI-spoken alert directly to the Tactical Operations
    //     Center so duty officers hear the crash immediately, even if they
    //     are away from the dashboard. Uses the same message text so the
    //     information is consistent across both channels.
    //
    // Both are non-fatal — a failure on either channel never blocks the
    // incident record from being saved or the Pusher broadcast from firing.
    public function notifyEmergencyContact(Incident $incident): void
    {
        $incident->loadMissing('rider.emergencyContacts');

        $message = $this->buildMessage($incident);

        // SMS to emergency contact
        $contact = $incident->rider?->emergencyContacts->first();
        if ($contact) {
            $this->sms->send($contact->phone_number, $message);
        }

        // Twilio AI voice call to TOC hotline
        $tocNumber = config('services.twilio.toc_number');
        if ($tocNumber) {
            $this->voiceCall->call($tocNumber, $message);
        }
    }

    private function buildMessage(Incident $incident): string
    {
        $riderName = $incident->rider?->full_name ?? 'A registered ImpactSense rider';
        $mapsLink  = "https://maps.google.com/?q={$incident->latitude},{$incident->longitude}";

        return "{$riderName} may have been in a motorcycle accident. Severity: {$incident->severity}. Location: {$mapsLink}";
    }
}
