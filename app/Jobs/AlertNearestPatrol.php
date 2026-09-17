<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Services\NearestPatrolService;
use App\Support\PatrolAlertSchema;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Alerts the nearest free patrol unit, then — if nobody has taken the call
 * when the acceptance window closes — the next nearest, up to max_units.
 *
 * Each round is its own job, dispatched with a delay by the round before.
 * Every round first checks whether the incident has been taken, by an
 * officer accepting or by the TOC dispatching, and stops quietly if so.
 */
class AlertNearestPatrol implements ShouldQueue
{
    use Queueable;

    // Once. Retrying after a partial failure would ring an officer who was
    // already rung; a missed alert is covered by the next round instead.
    public int $tries = 1;

    public bool $deleteWhenMissingModels = true;

    /**
     * @param  int[]  $alreadyAlerted
     */
    public function __construct(
        public readonly Incident $incident,
        public readonly int $round = 1,
        public readonly array $alreadyAlerted = [],
    ) {}

    /**
     * Where a crash report starts the chain.
     *
     * On the sync queue a dispatched job runs inside the request, and this
     * request is a device on a weak link with a 10 second timeout, already
     * waiting on the TOC call. A second Twilio call and a push inside that
     * window risk the device giving up and filing the same crash twice. So on
     * sync this waits until the response has been sent.
     */
    public static function start(Incident $incident): void
    {
        if (! PatrolAlertSchema::ready()) {
            Log::warning('Nearest-patrol alert skipped: run `php artisan migrate` first.', [
                'incident' => $incident->id,
            ]);
            return;
        }

        if (in_array(config('queue.default'), ['sync', 'null'], true)) {
            self::dispatchAfterResponse($incident);
        } else {
            self::dispatch($incident);
        }
    }

    public function handle(NearestPatrolService $patrols): void
    {
        $incident = $this->incident->fresh();

        if (! $incident) {
            return;
        }

        // Taken — the dispatch or the acceptance is already in the timeline.
        if ($incident->patrol_unit_id !== null || $incident->status !== 'pending') {
            return;
        }

        $radius   = rtrim(rtrim(number_format((float) config('services.patrol_alert.radius_km', 5), 1), '0'), '.');
        $maxUnits = max(1, (int) config('services.patrol_alert.max_units', 3));

        // Only true counts. False is the device's fallback point; null is a
        // report that never said, which is just as unsafe to measure from.
        if ($this->round === 1 && $incident->location_verified !== true) {
            $patrols->end($incident, $incident->location_verified === false
                ? 'the device had no GPS fix, so the location is not confirmed. The TOC will dispatch.'
                : 'the report did not confirm its GPS location. The TOC will dispatch.');
            return;
        }

        if ($this->round > $maxUnits) {
            $count = count($this->alreadyAlerted);
            $patrols->end($incident, sprintf(
                'no unit accepted after %d %s. The TOC will dispatch.',
                $count, $count === 1 ? 'alert' : 'alerts',
            ));
            return;
        }

        $next = $patrols->nearestFree($incident, $this->alreadyAlerted);

        if (! $next) {
            $patrols->end($incident, $this->round === 1
                ? "no on-duty unit within {$radius} km. The TOC will dispatch."
                : 'no other on-duty unit nearby. The TOC will dispatch.');
            return;
        }

        $canFollowUp = $patrols->canFollowUp();
        $wait        = max(15, (int) config('services.patrol_alert.accept_seconds', 90));

        $followUp = match (true) {
            ! $canFollowUp              => 'none: the queue runs inline, so only the nearest unit is alerted',
            $this->round >= $maxUnits   => "last unit; the TOC takes over if no one accepts in {$wait} s",
            default                     => "next unit in {$wait} s if no one accepts",
        };

        $patrols->alert($incident, $next['unit'], $next['km'], $this->round, $followUp);

        if ($canFollowUp) {
            // Dispatched even after the last unit, so the final round can
            // record that nobody accepted instead of the chain going silent.
            self::dispatch($incident, $this->round + 1, [...$this->alreadyAlerted, $next['unit']->id])
                ->delay(now()->addSeconds($wait));
        }
    }
}
