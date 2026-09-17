<?php

namespace App\Services;

use App\Events\PatrolNearestAlert;
use App\Models\Incident;
use App\Models\IncidentEvent;
use App\Models\PatrolUnit;
use Illuminate\Support\Facades\Log;

/**
 * Finds the closest patrol unit that can take a call, and alerts it.
 *
 * An alert is not a dispatch. Nothing here assigns the incident: the officer
 * accepts through the patrol app's existing "on my way" action, or the TOC
 * dispatches from the dashboard as it always has. That keeps a unit that
 * cannot respond from being recorded as responding, and keeps the TOC in
 * charge of the call.
 */
class NearestPatrolService
{
    private const EARTH_RADIUS_KM = 6371.0;

    public function __construct(
        private FcmService $fcm,
        private VoiceCallService $voiceCall,
        private GeocodingService $geocoding,
    ) {}

    /**
     * The closest unit that is free to respond, or null.
     *
     * @param  int[]  $excludeIds  units already alerted for this incident
     * @return array{unit: PatrolUnit, km: float}|null
     */
    public function nearestFree(Incident $incident, array $excludeIds = []): ?array
    {
        // Narrowed in SQL to units that could possibly qualify. The final
        // decision still goes through isFreeToRespond(), so there is exactly
        // one definition of "free" and this query cannot drift from it.
        $units = PatrolUnit::query()
            ->where('on_duty', true)
            ->where('status', '!=', 'dispatched')
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->where('last_seen_at', '>=', now()->subMinutes(PatrolUnit::ONLINE_WITHIN_MINUTES))
            ->when($excludeIds, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->get();

        return self::rank(
            $units,
            (float) $incident->latitude,
            (float) $incident->longitude,
            (float) config('services.patrol_alert.radius_km', 5),
            $excludeIds,
        )[0] ?? null;
    }

    /**
     * Free units within the radius, closest first. No database access, so the
     * selection rules can be tested on their own.
     *
     * Straight-line distance, not road distance. Inside one city that is a
     * close enough stand-in, and a road-distance API would put a paid,
     * failure-prone call in front of every alert.
     *
     * @param  iterable<PatrolUnit>  $units
     * @param  int[]  $excludeIds
     * @return array<int, array{unit: PatrolUnit, km: float}>
     */
    public static function rank(iterable $units, float $lat, float $lng, float $radiusKm, array $excludeIds = []): array
    {
        $ranked = [];

        foreach ($units as $unit) {
            if (in_array($unit->id, $excludeIds, true) || ! $unit->isFreeToRespond()) {
                continue;
            }

            $km = self::distanceKm($lat, $lng, (float) $unit->current_latitude, (float) $unit->current_longitude);

            if ($km <= $radiusKm) {
                $ranked[] = ['unit' => $unit, 'km' => round($km, 2)];
            }
        }

        usort($ranked, fn (array $a, array $b) => $a['km'] <=> $b['km']);

        return $ranked;
    }

    /** Great-circle (haversine) distance in kilometres. */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));
    }

    /**
     * Philippine mobile numbers to the +63 form Twilio requires. Officers
     * type them as 09..., 639... or +63...; anything that is not a mobile
     * number comes back null rather than being dialled as-is.
     */
    public static function toE164(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        return match (true) {
            (bool) preg_match('/^09\d{9}$/', $digits)  => '+63' . substr($digits, 1),
            (bool) preg_match('/^639\d{9}$/', $digits) => '+' . $digits,
            (bool) preg_match('/^9\d{9}$/', $digits)   => '+63' . $digits,
            default                                    => null,
        };
    }

    /**
     * Whether a follow-up alert can wait for the acceptance window. The sync
     * queue ignores delays and runs a job the moment it is dispatched, so a
     * "try the next unit in 90 seconds" would instead ring every unit in the
     * city at once.
     */
    public function canFollowUp(): bool
    {
        return ! in_array(config('queue.default'), ['sync', 'null'], true);
    }

    /**
     * Rings and pushes one unit. Every channel is guarded on its own: an
     * officer the call cannot reach may still get the push, and neither
     * failing stops the other or the timeline entry.
     */
    public function alert(Incident $incident, PatrolUnit $unit, float $km, int $round, ?string $followUp): void
    {
        $place    = $this->placeFor($incident);
        $reached  = [];

        try {
            broadcast(new PatrolNearestAlert($incident, $unit->id, $km));
            $reached[] = 'app';
        } catch (\Throwable $e) {
            Log::warning('Pusher broadcast failed (PatrolNearestAlert)', ['error' => $e->getMessage()]);
        }

        if ($unit->fcm_token) {
            $pushed = $this->fcm->sendToToken(
                $unit->fcm_token,
                'Nearest unit: possible accident',
                sprintf('%s km away at %s. Open ImpactSense to respond.', number_format($km, 1), $place ?? 'the pinned location'),
                [
                    'type'        => 'nearest_patrol_alert',
                    'incident_id' => (string) $incident->id,
                    'severity'    => (string) $incident->severity,
                    'address'     => (string) $place,
                    'latitude'    => (string) $incident->latitude,
                    'longitude'   => (string) $incident->longitude,
                    'distance_km' => (string) $km,
                ],
            );
            if ($pushed) {
                $reached[] = 'push';
            }
        }

        if (config('services.patrol_alert.call', true)) {
            $number = self::toE164($unit->mobile_number);

            if ($number === null) {
                Log::warning('Nearest-patrol call skipped: no usable mobile number', ['patrol_unit' => $unit->id]);
            } else {
                try {
                    if ($this->voiceCall->call($number, $this->spokenLines($incident, $km, $place))) {
                        $reached[] = 'call';
                    }
                } catch (\Throwable $e) {
                    Log::error('Nearest-patrol call failed', ['patrol_unit' => $unit->id, 'error' => $e->getMessage()]);
                }
            }
        }

        IncidentEvent::record($incident, IncidentEvent::PATROL_ALERTED, [
            'payload' => array_filter([
                'patrol_unit' => $unit->full_name,
                'badge'       => $unit->badge_number,
                'distance_km' => $km,
                'round'       => $round,
                'reached_by'  => $reached ? implode(', ', $reached) : 'nothing - check the unit\'s number and app',
                'follow_up'   => $followUp,
            ], fn ($v) => $v !== null),
        ]);
    }

    /** Records why the alert did not run, or why it stopped. */
    public function end(Incident $incident, string $reason): void
    {
        IncidentEvent::record($incident, IncidentEvent::PATROL_ALERT_ENDED, [
            'payload' => ['reason' => $reason],
        ]);
    }

    private function placeFor(Incident $incident): ?string
    {
        if (filled($incident->address)) {
            return $incident->address;
        }

        try {
            $address = $this->geocoding->reverse((float) $incident->latitude, (float) $incident->longitude);
        } catch (\Throwable $e) {
            Log::warning('Geocoding failed (nearest patrol)', ['incident' => $incident->id, 'error' => $e->getMessage()]);
            return null;
        }

        if ($address) {
            $incident->update(['address' => $address]);
        }

        return $address;
    }

    /**
     * Written for an officer who picks up on a motorcycle: the distance first,
     * because that is what decides whether they go, and no URL, which text to
     * speech would spell out letter by letter.
     *
     * @return string[]
     */
    private function spokenLines(Incident $incident, float $km, ?string $place): array
    {
        $where = $place
            ? "Location: {$place}."
            : sprintf(
                'Location: latitude %s, longitude %s.',
                number_format((float) $incident->latitude, 4),
                number_format((float) $incident->longitude, 4),
            );

        return [
            'Attention, patrol unit. This is ImpactSense.',
            sprintf('A possible motorcycle accident was detected about %s kilometres from your position.', number_format($km, 1)),
            $where,
            'Severity: ' . $incident->severity . '.',
            'You are the nearest available unit. Open the ImpactSense app to respond.',
            'The Tactical Operations Center has also been notified.',
        ];
    }
}
