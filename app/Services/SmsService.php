<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private const API_URL = 'https://api.semaphore.co/api/v4/messages';

    // Sends an SMS via Semaphore. Non-fatal if not configured or the request
    // fails — logs a warning and returns false rather than throwing, matching
    // FcmService's pattern so a notification failure never breaks the incident
    // flow that triggered it.
    public function send(string $phoneNumber, string $message): bool
    {
        $apiKey = config('services.semaphore.api_key');

        if (! $apiKey) {
            Log::warning('Semaphore not configured — skipping SMS.');
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

        $response = Http::asForm()->post(self::API_URL, $payload);

        if (! $response->successful()) {
            Log::error('Semaphore SMS send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        return true;
    }
}
