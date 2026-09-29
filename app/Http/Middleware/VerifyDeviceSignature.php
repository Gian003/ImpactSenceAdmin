<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Support\DeviceSignature;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks the signature on the three endpoints a device talks to.
 *
 * The rule is per-device, not global, so this can be deployed to a fleet that
 * is already in the field:
 *
 *   never signed before  -> unsigned is allowed, and a valid signature, the
 *                           first time one arrives, switches the device over
 *                           permanently
 *   has signed before    -> unsigned is refused
 *
 * A device therefore keeps working until it is provisioned, and once it is,
 * nothing can quietly push it back to unsigned — an attacker cannot strip the
 * header to get the old behaviour.
 *
 * Unknown device codes are passed through untouched: the controllers already
 * answer those with a clear 404, and repeating that logic here would only
 * give two different answers to the same question.
 */
class VerifyDeviceSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        // A server that has not run the migration cannot hold secrets, so it
        // cannot check signatures either. Everything carries on unsigned
        // rather than refusing a provisioned device that is doing it right.
        if (! Device::signingSupported()) {
            return $next($request);
        }

        $code = (string) ($request->input('device_code') ?? $request->query('device_code', ''));

        if ($code === '') {
            return $next($request);
        }

        $device = Device::where('device_code', $code)->first();

        if (! $device) {
            return $next($request);
        }

        $presented = (string) $request->header(DeviceSignature::HEADER_SIGNATURE, '');
        $counter   = (string) $request->header(DeviceSignature::HEADER_COUNTER, '');

        if ($presented === '' || $counter === '') {
            if ($device->requiresSignature()) {
                Log::warning('Unsigned report refused from a device that signs', [
                    'device_code' => $device->device_code,
                    'ip'          => $request->ip(),
                ]);

                return $this->refuse('This device must sign its reports.');
            }

            // Not provisioned yet. Allowed, and worth knowing about.
            Log::info('Unsigned device report accepted (not provisioned to sign yet)', [
                'device_code' => $device->device_code,
            ]);

            return $next($request);
        }

        if (! ctype_digit($counter)) {
            return $this->refuse('Malformed request counter.');
        }

        // The exact bytes the device signed: its JSON body, or for a GET the
        // query string as sent.
        $body = $request->isMethod('GET')
            ? (string) $request->getQueryString()
            : $request->getContent();

        if (! $device->signing_secret
            || ! DeviceSignature::matches($presented, $device->signing_secret, $code, $counter, $body)) {
            Log::warning('Device report refused: signature did not match', [
                'device_code' => $device->device_code,
                'ip'          => $request->ip(),
            ]);

            return $this->refuse('Signature does not match.');
        }

        // Replay protection. Equal counts too: a captured request carries the
        // counter it was signed with, so accepting it again would be the
        // replay this exists to stop.
        if ((int) $counter <= (int) $device->last_signature_counter) {
            Log::warning('Device report refused: counter already used', [
                'device_code' => $device->device_code,
                'presented'   => $counter,
                'last_seen'   => $device->last_signature_counter,
            ]);

            return $this->refuse('Request counter has already been used.');
        }

        $device->forceFill([
            'last_signature_counter' => (int) $counter,
            // First valid signature from this device: from now on, unsigned
            // reports claiming to be it are refused.
            'signature_required_since' => $device->signature_required_since ?? now(),
        ])->save();

        return $next($request);
    }

    private function refuse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
        ], 401);
    }
}
