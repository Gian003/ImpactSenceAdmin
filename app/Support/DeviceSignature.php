<?php

namespace App\Support;

/**
 * How a device proves a report came from it.
 *
 * The device signs, the server recomputes, and the secret itself never
 * travels — which is what makes this hold on plain HTTP, where a token or a
 * password in a header could simply be read off the wire and reused.
 *
 * The signed string is:
 *
 *     <device_code>\n<counter>\n<body>
 *
 * Body is the exact bytes sent (the JSON, or the query string for a GET), so
 * a signature covers the crash's coordinates and severity too, not just the
 * identity — an attacker cannot take a genuine signed report and change where
 * it says the rider is.
 *
 * COUNTER, not a timestamp. The device has no clock: no RTC battery, and no
 * NTP on the fallback path. It sends a number that only ever goes up, and the
 * server refuses anything at or below the highest it has already accepted, so
 * a captured request cannot be replayed. The firmware derives it from a boot
 * count held in flash, so it keeps climbing across reboots and power loss.
 */
final class DeviceSignature
{
    public const HEADER_SIGNATURE = 'X-Device-Signature';
    public const HEADER_COUNTER   = 'X-Device-Counter';

    /** The exact bytes both sides sign. */
    public static function payload(string $deviceCode, string $counter, string $body): string
    {
        return $deviceCode . "\n" . $counter . "\n" . $body;
    }

    /** Lower-case hex HMAC-SHA256. */
    public static function compute(string $secret, string $deviceCode, string $counter, string $body): string
    {
        return hash_hmac('sha256', self::payload($deviceCode, $counter, $body), $secret);
    }

    /**
     * Constant-time comparison. A plain === leaks, through how long it takes
     * to fail, roughly how much of a guess was right — which is enough to
     * recover a signature one character at a time.
     */
    public static function matches(
        string $presented,
        string $secret,
        string $deviceCode,
        string $counter,
        string $body,
    ): bool {
        return hash_equals(self::compute($secret, $deviceCode, $counter, $body), $presented);
    }
}
