<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Models\IncidentEvent;
use Illuminate\Console\Command;

class BackfillIncidentEvents extends Command
{
    protected $signature = 'incidents:backfill-events {--fresh : delete reconstructed rows first}';

    protected $description = 'Reconstruct incident event history from existing timestamp columns';

    /**
     * Builds a history for incidents that predate the event log.
     *
     * Every row it writes is flagged `reconstructed`, and the timeline shows
     * them as such. That flag is the point: these events were inferred from
     * timestamp columns after the fact, so the log knows *when* things
     * happened but cannot know *who* did them — nothing recorded that at the
     * time. Presenting a guess as a record would be worse than the gap.
     *
     * Deliberately conservative. It writes only what a column actually
     * evidences, and skips any incident that already has live events so a
     * re-run cannot bury real history under inference.
     */
    public function handle(): int
    {
        if ($this->option('fresh')) {
            $removed = IncidentEvent::where('reconstructed', true)->delete();
            $this->warn("Removed {$removed} previously reconstructed event(s).");
        }

        $written = 0;
        $skipped = 0;

        Incident::with('patrolUnit')->chunkById(200, function ($incidents) use (&$written, &$skipped) {
            foreach ($incidents as $incident) {
                // Anything with a genuine, live-logged event is left alone.
                if ($incident->events()->where('reconstructed', false)->exists()) {
                    $skipped++;
                    continue;
                }

                if ($incident->events()->exists()) {
                    $skipped++;
                    continue;
                }

                $written += $this->rebuild($incident);
            }
        });

        $this->info("Reconstructed {$written} event(s) across the incident history.");
        if ($skipped) {
            $this->line("Skipped {$skipped} incident(s) that already had events.");
        }

        return self::SUCCESS;
    }

    private function rebuild(Incident $incident): int
    {
        $unit = $incident->patrolUnit?->full_name;
        $rows = [];

        $rows[] = [IncidentEvent::REPORTED, $incident->created_at, null, 'pending', []];

        if ($incident->dispatched_at) {
            $rows[] = [IncidentEvent::DISPATCHED, $incident->dispatched_at, 'pending', 'dispatched',
                array_filter(['patrol_unit' => $unit])];
        }

        if ($incident->arrived_at) {
            $rows[] = [IncidentEvent::ARRIVED, $incident->arrived_at, 'dispatched', 'arrived',
                array_filter(['patrol_unit' => $unit])];
        }

        if ($incident->status === 'resolved' && $incident->resolved_at) {
            $rows[] = [IncidentEvent::RESOLVED, $incident->resolved_at, null, 'resolved', []];
        }

        // False alarms never had a column to write a time to, which is half
        // the reason this table exists. updated_at is the closest evidence
        // there is, and the reconstructed flag says not to trust it as exact.
        if ($incident->status === 'false_alarm') {
            $rows[] = [IncidentEvent::CANCELLED, $incident->updated_at ?? $incident->created_at,
                null, 'false_alarm', ['inferred_from' => 'updated_at']];
        }

        foreach ($rows as [$type, $at, $from, $to, $payload]) {
            IncidentEvent::create([
                'incident_id'   => $incident->id,
                'type'          => $type,
                'status_from'   => $from,
                'status_to'     => $to,
                // No actor is recorded rather than a guessed one. Nothing at
                // the time captured who acted, and inventing a name here would
                // put a person's name against an action on no evidence.
                'actor_type'    => null,
                'actor_id'      => null,
                'actor_name'    => null,
                'payload'       => $payload ?: null,
                'occurred_at'   => $at,
                'reconstructed' => true,
            ]);
        }

        return count($rows);
    }
}
