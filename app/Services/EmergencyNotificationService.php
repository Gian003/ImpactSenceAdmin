<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    /**
     * @param bool $sendSms   Text the rider's emergency contact (Semaphore).
     * @param bool $makeCall  Ring the TOC hotline (Twilio TTS).
     *
     * Both default to true, so a real crash is unaffected and every existing
     * caller keeps the behaviour it had. They exist for the TOC's
     * demonstration tool: an SMS costs Semaphore credits and a call costs
     * Twilio balance, and a rehearsal usually wants one without the other.
     */
    public function notifyEmergencyContact(
        Incident $incident,
        bool $sendSms = true,
        bool $makeCall = true
    ): void
    {
        if (! $sendSms && ! $makeCall) {
            return;
        }

        $incident->loadMissing('rider.emergencyContacts');

        // Resolved once and shared by both channels. Persisted back onto the
        // incident when it had none, so every other page showing this crash
        // gets a real place name too instead of a blank Address column.
        // Even the geocoder is an outbound call, and this whole method runs
        // inside the crash-report request.
        try {
            $place = $this->resolvePlace($incident);
        } catch (\Throwable $e) {
            Log::warning('Geocoding failed', ['incident' => $incident->id, 'error' => $e->getMessage()]);
            $place = $incident->address;
        }

        // Each channel is guarded on its own. The comment above has always
        // claimed these are non-fatal, and that was true of a failed API
        // response but not of a network exception: a Semaphore timeout threw
        // straight out of here and took the caller's request with it. Wrapping
        // them separately also means a dead SMS gateway no longer prevents the
        // TOC hotline from ringing — previously the first throw stopped both.
        $contact = $sendSms ? $incident->rider?->emergencyContacts->first() : null;
        if ($contact) {
            try {
                $this->sms->send($contact->phone_number, $this->buildSmsMessage($incident, $place));
            } catch (\Throwable $e) {
                Log::error('Emergency SMS failed', [
                    'incident' => $incident->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        // Twilio AI voice call to TOC hotline
        $tocNumber = $makeCall ? config('services.twilio.toc_number') : null;
        if ($tocNumber) {
            try {
            // Stored so the Call Recordings page can say which crash each
            // recording was about — Twilio's recordings carry a call_sid,
            // and this is the only thing tying that back to an incident.
            // Still non-fatal: a null SID (Twilio down or unconfigured) just
            // leaves the incident unlinked rather than failing the report.
                $callSid = $this->voiceCall->call($tocNumber, $this->buildSpokenLines($incident, $place));

                if ($callSid) {
                    $incident->update(['twilio_call_sid' => $callSid]);
                }
            } catch (\Throwable $e) {
                Log::error('TOC voice call failed', [
                    'incident' => $incident->id, 'error' => $e->getMessage(),
                ]);
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

    // SMS keeps the maps link — a tappable pin is the single most useful thing
    // you can hand someone reading this on a phone.
    //
    // Written to fit 160 characters, because Semaphore bills per segment and
    // the old wording ran to 175-208 — every real crash cost two credits
    // instead of one, for no extra information. Three things bought that back:
    // shorter phrasing, coordinates at 4 decimal places (~11 m, which is finer
    // than the GPS itself), and a cap on the geocoded place name, which is the
    // only part with no upper bound.
    //
    // The place is trimmed to fit, and dropped entirely if it still will not.
    // A fixed cap was not enough: the rider's name has no upper bound either,
    // and a long one pushed the total back over on its own. Shortening what is
    // least useful is better than mangling a name the family has to recognise,
    // so the order of sacrifice is place, then nothing - the name, the
    // severity and the map link always survive.
    private const SEGMENT = 160;

    private function buildSmsMessage(Incident $incident, ?string $place): string
    {
        $riderName = $this->riderName($incident);
        $mapsLink  = sprintf(
            'https://maps.google.com/?q=%.4f,%.4f',
            (float) $incident->latitude,
            (float) $incident->longitude,
        );

        $head = "ImpactSense: {$riderName} may have crashed. "
              . "Severity: {$incident->severity}.";
        $tail = " {$mapsLink}";

        if (blank($place)) {
            return $head . $tail;
        }

        $budget = self::SEGMENT - strlen($head) - strlen($tail) - 2; // " " and "."
        $clean  = rtrim(trim($place), " ,.");

        if ($budget < 12) {
            // No room worth having. A three-word fragment of a street name
            // helps nobody, and the map link already carries the position.
            return $head . $tail;
        }

        if (strlen($clean) > $budget) {
            $clean = rtrim(Str::limit($clean, $budget, ''), " ,.");
        }

        return $head . ' ' . $clean . '.' . $tail;
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
