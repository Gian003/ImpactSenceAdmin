<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Whether anything is actually draining the queue.
 *
 * Crash notifications — the push to the rider, the SMS to their emergency
 * contact, the Twilio call to the TOC hotline — are queued rather than sent
 * inside the request. That is right for the device, which gets told its crash
 * was recorded in milliseconds instead of seconds, but it moves the failure
 * mode: if the connection is a real queue and no worker is running, those
 * notifications are not slow, they simply never happen. Nothing errors.
 * Nothing appears in a log. The rows just sit there.
 *
 * A silent failure on an emergency path is worse than a loud one, so this
 * exists to make it visible.
 */
class QueueHealth
{
    /** A worker picks work up in well under a second; this much lag is a warning. */
    private const LAGGING_SECONDS = 30;

    /** Past this, assume nothing is running rather than that it is busy. */
    private const STALLED_SECONDS = 120;

    /**
     * @return object{connection:string, queued:bool, pending:int, failed:int,
     *                oldest_seconds:?int, status:string, message:string}
     */
    public static function check(): object
    {
        $connection = config('queue.default');

        // On sync there is no queue to stall: the work runs inside the
        // request, which is slower but cannot silently vanish.
        if ($connection === 'sync') {
            return self::result($connection, false, 0, self::failedCount(), null, 'not_queued',
                'Notifications run inside the request. Nothing is queued, so nothing can stall — '
                . 'but a slow Google or Twilio delays the device.');
        }

        if ($connection !== 'database') {
            return self::result($connection, true, 0, self::failedCount(), null, 'unknown',
                "Queue connection is \"{$connection}\"; this check only understands database and sync.");
        }

        $pending = DB::table('jobs')->count();
        $failed  = self::failedCount();

        $oldest = DB::table('jobs')->min('available_at');
        $age    = $oldest === null ? null : max(0, now()->getTimestamp() - (int) $oldest);

        [$status, $message] = match (true) {
            $age !== null && $age >= self::STALLED_SECONDS => ['stalled',
                'Jobs have been waiting ' . self::human($age) . '. No worker appears to be running, '
                . 'so crash notifications are NOT being sent. Start one with: php artisan queue:work'],
            $age !== null && $age >= self::LAGGING_SECONDS => ['lagging',
                'Jobs have been waiting ' . self::human($age) . '. The worker may be overloaded or restarting.'],
            $failed > 0 => ['failing',
                $failed . ' ' . ($failed === 1 ? 'job has' : 'jobs have') . ' failed. '
                . 'Inspect with: php artisan queue:failed'],
            default => ['healthy', 'A worker is keeping up.'],
        };

        return self::result($connection, true, $pending, $failed, $age, $status, $message);
    }

    /** True when an operator should be told something is wrong. */
    public static function needsAttention(): bool
    {
        return in_array(self::check()->status, ['stalled', 'lagging', 'failing'], true);
    }

    private static function failedCount(): int
    {
        return DB::getSchemaBuilder()->hasTable('failed_jobs')
            ? DB::table('failed_jobs')->count()
            : 0;
    }

    private static function human(int $seconds): string
    {
        return match (true) {
            $seconds < 60    => $seconds . ' seconds',
            $seconds < 3600  => round($seconds / 60) . ' minutes',
            $seconds < 86400 => round($seconds / 3600, 1) . ' hours',
            default          => round($seconds / 86400, 1) . ' days',
        };
    }

    private static function result(
        string $connection, bool $queued, int $pending, int $failed,
        ?int $oldest, string $status, string $message,
    ): object {
        return (object) [
            'connection'     => $connection,
            'queued'         => $queued,
            'pending'        => $pending,
            'failed'         => $failed,
            'oldest_seconds' => $oldest,
            'status'         => $status,
            'message'        => $message,
        ];
    }
}
