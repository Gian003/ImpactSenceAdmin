<?php

namespace App\Services;

use App\Models\Incident;

class EmergencyNotificationService
{
    public function __construct(
        private SmsService $sms,
        private VoiceCallService $voiceCall,
        private GeocodingService $geocoding,
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

        // Resolved once and shared by both channels. Persisted back onto the
        // incident when it had none, so every other page showing this crash
        // gets a real place name too instead of a blank Address column.
        $place = $this->resolvePlace($incident);

        // SMS to emergency contact
        $contact = $incident->rider?->emergencyContacts->first();
        if ($contact) {
            $this->sms->send($contact->phone_number, $this->buildSmsMessage($incident, $place));
        }

        // Twilio AI voice call to TOC hotline
        $tocNumber = config('services.twilio.toc_number');
        if ($tocNumber) {
            // Stored so the Call Recordings page can say which crash each
            // recording was about — Twilio's recordings carry a call_sid,
            // and this is the only thing tying that back to an incident.
            // Still non-fatal: a null SID (Twilio down or unconfigured) just
            // leaves the incident unlinked rather than failing the report.
            $callSid = $this->voiceCall->call($tocNumber, $this->buildSpokenLines($incident, $place));

            if ($callSid) {
                $incident->update(['twilio_call_sid' => $callSid]);
            }
        }
    }

    // A device report only carries coordinates, so most incidents arrive
    // with no address at all. Look one up, and keep it.
    private function resolvePlace(Incident $incident): ?string
    {
        if (filled($incident->address)) {
            return $incident->address;
        }

        if ($incident->latitude === null || $incident->longitude === null) {
            return null;
        }

        $address = $this->geocoding->reverse((float) $incident->latitude, (float) $incident->longitude);

        if ($address) {
            $incident->update(['address' => $address]);
        }

        return $address;
    }

    // SMS keeps the maps link — a tappable pin is the single most useful
    // thing you can hand someone reading this on a phone.
    private function buildSmsMessage(Incident $incident, ?string $place): string
    {
        $riderName = $this->riderName($incident);
        $mapsLink  = "https://maps.google.com/?q={$incident->latitude},{$incident->longitude}";
        $where     = $place ? " near {$place}." : '.';

        return "{$riderName} may have been in a motorcycle accident{$where} "
            . "Severity: {$incident->severity}. Location: {$mapsLink}";
    }

    /**
     * One sentence per line, spoken with a pause between each.
     *
     * Deliberately never includes the maps URL: read aloud, Polly spells it
     * out character by character ("h-t-t-p-s colon slash slash maps dot
     * google dot com...") which ate most of the old call and told the
     * officer nothing. A place name is what someone can actually act on;
     * coordinates are the fallback, rounded to ~11 m rather than read out to
     * seven decimal places.
     *
     * @return string[]
     */
    private function buildSpokenLines(Incident $incident, ?string $place): array
    {
        $where = $place
            ? "Location: {$place}."
            : sprintf(
                'Location: latitude %s, longitude %s.',
                number_format((float) $incident->latitude, 4),
                number_format((float) $incident->longitude, 4),
            );

        return [
            'Attention. ImpactSense has detected a possible motorcycle accident.',
            'Rider: ' . $this->riderName($incident) . '.',
            'Severity: ' . $incident->severity . '.',
            $where,
            'Please check the ImpactSense dashboard and dispatch a patrol unit.',
        ];
    }

    // Names are stored assembled from parts, and a missing part can leave a
    // literal "N/A" sitting in the middle of the string — which the TTS
    // dutifully reads out as part of the rider's name. The cleanup itself
    // lives on the User model, shared with the IRF's name fields.
    private function riderName(Incident $incident): string
    {
        $name = \App\Models\User::cleanName($incident->rider?->full_name);

        return $name !== '' ? $name : 'A registered ImpactSense rider';
    }
}
