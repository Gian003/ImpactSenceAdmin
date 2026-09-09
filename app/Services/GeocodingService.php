<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Turns coordinates into a human-readable place name.
//
// The IoT device only ever reports latitude/longitude (see the firmware's
// onCrashConfirmed) - it has no way to know what that location is called - so
// incidents created from a device report land with address = NULL. That's
// fine on a map, which can just drop a pin, but it's useless in a spoken
// alert: "location: fifteen point nine eight six five" tells a duty officer
// nothing they can act on.
class GeocodingService
{
    // Deliberately short. This sits in the crash-alert path, where a slow
    // third-party lookup delaying the call matters far more than a missing
    // street name - the caller falls back to plain coordinates.
    private const TIMEOUT_SECONDS = 4;

    public function reverse(float $latitude, float $longitude): ?string
    {
        $key = config('services.google_maps.key');

        if (! $key) {
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$latitude},{$longitude}",
                    'key'    => $key,
                ]);

            if (! $response->successful()) {
                Log::warning('Reverse geocode HTTP failure', ['status' => $response->status()]);
                return null;
            }

            $body = $response->json();

            // Google reports its own errors in the body with a 200 status,
            // so the HTTP check above isn't enough on its own.
            if (($body['status'] ?? null) !== 'OK') {
                Log::warning('Reverse geocode returned no result', [
                    'status' => $body['status'] ?? 'unknown',
                ]);
                return null;
            }

            $address = $body['results'][0]['formatted_address'] ?? null;

            return $address ? $this->stripPlusCode($address) : null;
        } catch (\Throwable $e) {
            // Never let a geocode failure break an emergency alert.
            Log::warning('Reverse geocode failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Where there's no precise street address, Google prefixes the result
    // with a Plus Code - "XPP3+R87, San Vicente, Santa Maria, Pangasinan".
    // It's a useful map reference but unusable in speech: read aloud it
    // becomes "X P P 3 plus R 8 7", which is precisely the kind of noise
    // that made the old maps-URL alert so hard to follow. The place names
    // after it are the part a person can actually act on.
    private function stripPlusCode(string $address): string
    {
        $stripped = preg_replace('/^[A-Z0-9]{4,}\+[A-Z0-9]{2,},?\s*/i', '', $address);

        // If the code was the entire result, keep the original rather than
        // handing back an empty string.
        return trim($stripped) !== '' ? trim($stripped) : $address;
    }
}
