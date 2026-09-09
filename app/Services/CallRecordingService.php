<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

// Reads back the recordings Twilio makes of the TTS alert calls placed by
// VoiceCallService. Recordings live in Twilio, not in this database - there's
// no local table mirroring them, so this is a thin read-through to their API
// rather than something that can drift out of sync.
class CallRecordingService
{
    public function isConfigured(): bool
    {
        return (bool) (config('services.twilio.account_sid')
            && config('services.twilio.auth_token'));
    }

    /**
     * Most recent recordings first. Returns a plain array of Twilio
     * RecordingInstance objects, or an empty array if Twilio isn't
     * configured or the API call fails - a dashboard page shouldn't 500
     * because an external service is unreachable.
     */
    public function recent(int $limit = 50): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $client = new Client(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token'),
            );

            return $client->recordings->read([], $limit);
        } catch (TwilioException $e) {
            Log::error('Twilio recording list failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fetches the actual audio bytes for one recording.
     *
     * Twilio's media URLs require HTTP Basic auth with the account
     * credentials, so the browser can't hit them directly - pointing an
     * <audio src> at Twilio would just 401, and putting the auth token in
     * the page to work around that would leak it to anyone who views
     * source. This pulls the bytes server-side instead so the credentials
     * never leave the server.
     *
     * Alert calls are a few seconds of speech (tens of KB), so buffering
     * the whole file is fine here; this would want real streaming if it
     * ever had to serve long recordings.
     *
     * @return string|null Raw mp3 bytes, or null if the fetch failed.
     */
    public function media(string $recordingSid): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $accountSid = config('services.twilio.account_sid');

        $response = Http::withBasicAuth($accountSid, config('services.twilio.auth_token'))
            ->timeout(20)
            ->get("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Recordings/{$recordingSid}.mp3");

        if (! $response->successful()) {
            Log::error('Twilio recording media fetch failed', [
                'recording_sid' => $recordingSid,
                'status'        => $response->status(),
            ]);
            return null;
        }

        return $response->body();
    }
}
