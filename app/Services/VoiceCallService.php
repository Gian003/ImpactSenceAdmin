<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

class VoiceCallService
{
    // Slower than Polly's default. A duty officer hears this once, cold,
    // often while doing something else - the message being a second longer
    // matters far less than it being caught first time.
    private const SPEECH_RATE = '85%';

    // Long enough to separate each fact into its own beat, short enough that
    // the call doesn't drag.
    private const SENTENCE_PAUSE = '700ms';

    // Configurable so the voice can be changed without a code edit if it
    // doesn't sound right on a real handset.
    private function voice(): string
    {
        return config('services.twilio.voice') ?: 'Polly.Matthew-Neural';
    }

    // Places a call and speaks $lines via Twilio's TTS, one sentence per
    // element, paced with pauses between them.
    //
    // Takes separate lines rather than one blob deliberately: TTS run
    // together at full speed is exactly what made the earlier version hard
    // to follow. Each line becomes its own SSML sentence with a pause after
    // it, which is what turns a rushed paragraph into something a person can
    // actually take down.
    //
    // The TwiML (the instruction telling Twilio's servers what to say) is
    // passed inline rather than as a webhook URL Twilio would fetch. That
    // started out as a workaround for the app only being reachable on the
    // LAN; it's since been exposed publicly through a Cloudflare tunnel, so
    // a webhook would now resolve - but inline TwiML is kept deliberately,
    // since there's no reason to stand up and secure a public endpoint just
    // to hand Twilio a fixed sentence it could have been given upfront.
    //
    // Returns Twilio's Call SID on success, or null if the call wasn't
    // placed. The SID is what ties an incident to its recording later (see
    // CallRecordingService) - Twilio's recordings carry the call_sid they
    // belong to, and nothing else links the two.
    //
    // @param string[] $lines
    public function call(string $phoneNumber, array $lines): ?string
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken  = config('services.twilio.auth_token');
        $fromNumber = config('services.twilio.from_number');

        if (! $accountSid || ! $authToken || ! $fromNumber) {
            Log::warning('Twilio not configured — skipping voice call.');
            return null;
        }

        $lines = array_values(array_filter(array_map('trim', $lines)));

        if (! $lines) {
            Log::warning('Voice call skipped — nothing to say.');
            return null;
        }

        $twiml = new VoiceResponse();

        // A short beat before speaking: without it the first word or two
        // gets clipped by the handset still settling after pickup, which
        // is a bad word to lose when it's the rider's name.
        $twiml->pause(['length' => 1]);

        $say = $twiml->say('', ['voice' => $this->voice(), 'language' => 'en-US']);
        $prosody = $say->prosody('', ['rate' => self::SPEECH_RATE]);

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $prosody->break_(['time' => self::SENTENCE_PAUSE]);
            }
            $prosody->s($line);
        }

        try {
            $client = new Client($accountSid, $authToken);

            $call = $client->calls->create($phoneNumber, $fromNumber, [
                'twiml' => (string) $twiml,
                // Twilio records the call on its own servers - the exact
                // audio stream sent down the line, so it captures the spoken
                // alert cleanly with none of the speaker/room noise a
                // phone-side recording would pick up. Surfaced in the
                // dashboard at toc/call-recordings.
                'record' => true,
            ]);

            return $call->sid;
        } catch (TwilioException $e) {
            Log::error('Twilio voice call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
