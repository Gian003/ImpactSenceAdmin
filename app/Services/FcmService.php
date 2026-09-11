<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private const TOKEN_CACHE_KEY = 'fcm_access_token';
    private const TOKEN_URL       = 'https://oauth2.googleapis.com/token';
    private const FCM_BASE        = 'https://fcm.googleapis.com/v1/projects';

    // Send a notification to a single FCM token.
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        $projectId   = config('services.fcm.project_id');
        $accessToken = $this->getAccessToken();

        if (! $projectId || ! $accessToken) {
            Log::warning('FCM not configured — skipping push notification.');
            return false;
        }

        try {
            $response = $this->client($accessToken)->post(
                self::FCM_BASE . "/{$projectId}/messages:send",
                [
                    'message' => [
                        'token'        => $fcmToken,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data'         => array_map('strval', $data),
                        'android'      => ['priority' => 'high'],
                        'apns'         => ['headers' => ['apns-priority' => '10']],
                    ],
                ]
            );
        } catch (\Throwable $e) {
            // A timeout or DNS failure used to escape this method and take the
            // caller with it. On the crash-report endpoint that meant an
            // incident was saved to the database and the device still received
            // a 500 — and a device that retries files the same crash twice.
            // Google being unreachable is not a reason to lose a crash report.
            Log::error('FCM send threw', ['error' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('FCM send failed', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        }

        return true;
    }

    // Convenience: notify a rider.
    public function notifyRider(\App\Models\User $rider, string $title, string $body, array $data = []): void
    {
        if ($rider->fcm_token) {
            $this->sendToToken($rider->fcm_token, $title, $body, $data);
        }
    }

    // Convenience: notify a patrol unit.
    public function notifyPatrol(\App\Models\PatrolUnit $patrol, string $title, string $body, array $data = []): void
    {
        if ($patrol->fcm_token) {
            $this->sendToToken($patrol->fcm_token, $title, $body, $data);
        }
    }

    // Obtain a short-lived OAuth2 access token from the service account JSON.
    private function getAccessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3000, function () {
            $serviceAccount = config('services.fcm.service_account_json');

            if (! $serviceAccount) {
                return null;
            }

            // Resolve relative paths from the project root so this works
            // regardless of the working directory (artisan serve, web server, etc.)
            $resolvedPath = str_starts_with($serviceAccount, '/')
                || str_contains($serviceAccount, ':') // Windows absolute path
                ? $serviceAccount
                : base_path($serviceAccount);

            $credentials = json_decode(
                is_file($resolvedPath) ? file_get_contents($resolvedPath) : $serviceAccount,
                true
            );

            if (! $credentials) {
                return null;
            }

            $now   = time();
            $claim = [
                'iss'   => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => self::TOKEN_URL,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ];

            $jwt = $this->buildJwt($credentials['private_key'], $claim);

            try {
                $response = Http::asForm()->timeout(8)->post(self::TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]);
            } catch (\Throwable $e) {
                // Bounded and swallowed: this call sits in the request path of
                // the crash endpoint, so an unreachable Google must cost a few
                // seconds and a log line, not the report.
                Log::error('FCM token exchange threw', ['error' => $e->getMessage()]);

                return null;
            }

            return $response->successful() ? $response->json('access_token') : null;
        });
    }

    private function buildJwt(string $privateKey, array $claims): string
    {
        $header  = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64url_encode(json_encode($claims));
        $data    = "$header.$payload";

        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return "$data." . base64url_encode($signature);
    }

    private function client(string $accessToken): PendingRequest
    {
        return Http::withToken($accessToken)->acceptJson();
    }
}

if (! function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
