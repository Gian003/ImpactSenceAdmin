<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

class VoiceCallService
{
    // Places a call and speaks $message via Twilio's TTS. The TwiML (the
    // instruction telling Twilio's servers what to say) is passed inline
    // rather than as a webhook URL Twilio would fetch - this app isn't
    // publicly reachable from the internet, only the LAN, so a URL-based
    // callback would never resolve. Inline TwiML sidesteps that entirely.
    public function call(string $phoneNumber, string $message): bool
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken  = config('services.twilio.auth_token');
        $fromNumber = config('services.twilio.from_number');

        if (! $accountSid || ! $authToken || ! $fromNumber) {
            Log::warning('Twilio not configured — skipping voice call.');
            return false;
        }

        $twiml = new VoiceResponse();
        $twiml->say($message, ['voice' => 'Polly.Matthew']);

        try {
            $client = new Client($accountSid, $authToken);

            $client->calls->create($phoneNumber, $fromNumber, [
                'twiml' => (string) $twiml,
            ]);

            return true;
        } catch (TwilioException $e) {
            Log::error('Twilio voice call failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
