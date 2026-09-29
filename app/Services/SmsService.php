<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Outbound SMS, over whichever gateway is actually available.
 *
 * Semaphore first: it is PH-local, cheap, and delivers reliably to Philippine
 * numbers because the sender ID is registered domestically.
 *
 * Twilio second, because there is already a funded Twilio account here for the
 * TOC voice call, and a drill on the dashboard should not be blocked by a
 * Semaphore top-up that cannot be paid for. Delivery from an international
 * number into PH is less certain - carriers here filter A2P traffic without a
 * registered sender ID - so this is a fallback, not a replacement. Send one to
 * your own handset and confirm it lands before relying on it in front of a
 * panel.
 *
 * Non-fatal throughout: a notification failure never breaks the incident flow
 * that triggered it.
 */
class SmsService
{
    private const SEMAPHORE_URL = 'https://api.semaphore.co/api/v4/messages';

    public function send(string $phoneNumber, string $message): bool
    {
        // Rehearsal switch — see config/services.php. The text is logged so a
        // drill can still show exactly what the family would have received.
        if (! config('services.outbound.sms', true)) {
            Log::info('Outbound SMS is switched off — text not sent.', [
                'to'      => $phoneNumber,
                'message' => $message,
            ]);

            return false;
        }

        if ($this->sendViaSemaphore($phoneNumber, $message)) {
            return true;
        }

        if ($this->sendViaTwilio($phoneNumber, $message)) {
            return true;
        }

        Log::error('SMS not sent: every gateway refused or is unconfigured.', [
            'to' => $phoneNumber,
        ]);

        return false;
    }

    private function sendViaSemaphore(string $phoneNumber, string $message): bool
    {
        $apiKey = config('services.semaphore.api_key');

        if (! $apiKey) {
            Log::info('Semaphore has no API key — trying the next gateway.');
            return false;
        }

        $payload = [
            'apikey'  => $apiKey,
            'number'  => $phoneNumber,
            'message' => $message,
        ];

        if ($senderName = config('services.semaphore.sender_name')) {
            $payload['sendername'] = $senderName;
        }

        try {
            $response = Http::asForm()->timeout(15)->post(self::SEMAPHORE_URL, $payload);
        } catch (\Throwable $e) {
            // Bounded and swallowed for the same reason FcmService swallows
            // its own: this sits in the request path of a crash report, and an
            // unreachable gateway must not cost the report.
            Log::warning('Semaphore threw — trying the next gateway.', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        if (! $response->successful()) {
            // Running out of credits lands here, which is exactly the case the
            // fallback below exists for.
            Log::warning('Semaphore refused the message — trying the next gateway.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        Log::info('SMS sent via Semaphore.', ['to' => $phoneNumber]);

        return true;
    }

    private function sendViaTwilio(string $phoneNumber, string $message): bool
    {
        $sid   = config('services.twilio.account_sid');
        $token = config('services.twilio.auth_token');
        $from  = config('services.twilio.from_number');

        if (! $sid || ! $token || ! $from) {
            Log::warning('Twilio is not configured — no SMS gateway left to try.');
            return false;
        }

        // Twilio insists on E.164. Numbers are typed in and stored as
        // 09xxxxxxxxx, which Semaphore accepts as-is, so the conversion has to
        // happen here rather than at the call site. Reuses the one parser
        // already in the codebase so there is a single definition of what
        // counts as a PH mobile number.
        $to = NearestPatrolService::toE164($phoneNumber);

        if ($to === null) {
            Log::warning('Twilio SMS skipped: not a usable PH mobile number.', [
                'to' => $phoneNumber,
            ]);
            return false;
        }

        try {
            (new Client($sid, $token))->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::error('Twilio SMS failed', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }

        Log::info('SMS sent via Twilio (Semaphore was unavailable).', ['to' => $to]);

        return true;
    }
}
