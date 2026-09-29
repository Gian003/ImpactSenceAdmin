<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

class VoiceCallService
{
    // A shade under Polly's natural pace. 85% was slow enough to read as
    // laboured rather than calm, and a dispatcher waiting through it is a
    // dispatcher not yet moving. 95% is unhurried without dragging - still
    // clearly slower than someone in a panic, which is the effect worth having.
    private const SPEECH_RATE = '95%';

    // Enough to give each fact its own beat. Shortened along with the rate so
    // the pauses stay proportionate to the speech, instead of turning the call
    // into a series of gaps.
    private const SENTENCE_PAUSE = '500ms';

    // Configurable so the voice can be changed without a code edit if it does
    // not sound right on a real handset.
    //
    // Joanna-Neural is even, articulate and free of the urgency an emergency
    // announcement does not need - the words already carry that, and a voice
    // that sounds alarmed makes a listener slower, not faster. Its higher
    // fundamental also cuts through the background noise these calls are
    // actually heard in, which a lower male voice does not.
    //
    // Worth trying on a real phone before settling:
    //   Polly.Amy-Neural      British, calmer still, a little more formal
    //   Polly.Matthew-Neural  US male, the previous default
    //   Polly.Brian-Neural    British male
    private function voice(): string
    {
        return config('services.twilio.voice') ?: 'Polly.Joanna-Neural';
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
        // Rehearsal switch — see config/services.php. Checked before anything
        // else so no call site can route around it, and logged loudly enough
        // that a silent demo is never mistaken for a broken one.
        if (! config('services.outbound.calls', true)) {
            Log::info('Outbound calls are switched off — call not placed.', [
                'to'    => $phoneNumber,
                'would_have_said' => implode(' ', $lines),
            ]);

            return null;
        }

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
