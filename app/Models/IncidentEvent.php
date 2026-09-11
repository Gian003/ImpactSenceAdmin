<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * One thing that happened to an incident. Append-only — see the migration.
 */
class IncidentEvent extends Model
{
    use HasFactory;

    public const REPORTED           = 'reported';
    public const DISPATCHED         = 'dispatched';
    public const REASSIGNED         = 'reassigned';
    public const ARRIVED            = 'arrived';
    public const RESOLVED           = 'resolved';
    public const CANCELLED          = 'cancelled';
    public const REOPENED           = 'reopened';
    public const FIELD_REPORT_FILED = 'field_report_filed';

    protected $fillable = [
        'incident_id', 'type', 'status_from', 'status_to',
        'actor_type', 'actor_id', 'actor_name', 'payload',
        'occurred_at', 'reconstructed',
    ];

    protected function casts(): array
    {
        return [
            'payload'       => 'array',
            'occurred_at'   => 'datetime',
            'reconstructed' => 'boolean',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Appends an event.
     *
     * The actor is resolved here rather than at each call site so no route
     * can quietly forget to record who acted — the omission this whole table
     * exists to fix. Pass $actor explicitly for anything the session cannot
     * identify, such as a patrol unit acting through the API.
     */
    public static function record(
        Incident $incident,
        string $type,
        array $attributes = [],
        ?Model $actor = null,
    ): self {
        [$actorType, $actorId, $actorName] = self::resolveActor($actor);

        return self::create([
            'incident_id'   => $incident->id,
            'type'          => $type,
            'status_from'   => $attributes['status_from'] ?? null,
            'status_to'     => $attributes['status_to'] ?? null,
            'actor_type'    => $actorType,
            'actor_id'      => $actorId,
            'actor_name'    => $actorName,
            'payload'       => $attributes['payload'] ?? null,
            'occurred_at'   => $attributes['occurred_at'] ?? now(),
            'reconstructed' => $attributes['reconstructed'] ?? false,
        ]);
    }

    /**
     * @return array{0:string, 1:?int, 2:?string}
     */
    private static function resolveActor(?Model $actor): array
    {
        if ($actor instanceof PatrolUnit) {
            return ['patrol', $actor->id, $actor->full_name];
        }

        if ($actor instanceof User) {
            return ['rider', $actor->id, User::cleanName($actor->full_name) ?: null];
        }

        if ($actor !== null) {
            return ['staff', $actor->getKey(), $actor->full_name ?? null];
        }

        foreach (['toc' => 'toc', 'investigation' => 'investigation'] as $guard => $label) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                return [$label, $user->getKey(), $user->full_name ?? null];
            }
        }

        // A device pushing a crash alert, or a console command. Naming it
        // "system" is honest; leaving it blank would read as an oversight.
        return ['system', null, null];
    }

    /** How the actor should be named in a timeline. */
    public function actorLabel(): string
    {
        if ($this->actor_name) {
            return $this->actor_name;
        }

        return match ($this->actor_type) {
            'patrol'        => 'a patrol unit',
            'toc'           => 'the TOC desk',
            'investigation' => 'an investigator',
            'rider'         => 'the rider',
            default         => 'the system',
        };
    }

    /**
     * The event as a sentence. Phrased as what was recorded rather than what
     * is true, and never claims an actor the log does not have.
     */
    public function describe(): string
    {
        $who  = $this->actorLabel();
        $unit = $this->payload['patrol_unit'] ?? null;

        return match ($this->type) {
            self::REPORTED => $this->actor_type === 'system'
                ? 'Incident reported by the device'
                : "Incident reported by {$who}",
            self::DISPATCHED => $unit
                ? "{$unit} dispatched by {$who}"
                : "Patrol dispatched by {$who}",
            self::REASSIGNED => $unit
                ? "Reassigned to {$unit} by {$who}"
                    . (isset($this->payload['previous_unit'])
                        ? " (was {$this->payload['previous_unit']})" : '')
                : "Reassigned by {$who}",
            self::ARRIVED  => $unit
                ? "{$unit} reported on scene"
                : "Responding unit reported on scene",
            self::RESOLVED => "Marked resolved by {$who}",
            self::CANCELLED => "Cancelled as a false alarm by {$who}",
            self::REOPENED => "Reopened by {$who}",
            self::FIELD_REPORT_FILED => "Field report filed by {$who}"
                . (isset($this->payload['photo_count']) && $this->payload['photo_count'] > 0
                    ? " with {$this->payload['photo_count']} photograph"
                        . ($this->payload['photo_count'] === 1 ? '' : 's')
                    : ''),
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * A glyph for the feed. Paired with colour() rather than replacing it:
     * colour alone leaves the feed unreadable to anyone who cannot separate
     * the hues, and a dispatch and a cancellation looked identical at 9px.
     */
    public function glyph(): string
    {
        return match ($this->type) {
            self::REPORTED           => '!',
            self::DISPATCHED         => "\u{2192}",
            self::REASSIGNED         => "\u{21BB}",
            self::ARRIVED            => "\u{25CF}",
            self::RESOLVED           => "\u{2713}",
            self::CANCELLED          => "\u{2715}",
            self::REOPENED           => "\u{21BA}",
            self::FIELD_REPORT_FILED => "\u{270E}",
            default                  => "\u{2022}",
        };
    }

    /** Colour for the timeline dot — matches the status palette elsewhere. */
    public function colour(): string
    {
        return match ($this->type) {
            self::REPORTED           => '#b91c1c',
            self::DISPATCHED,
            self::REASSIGNED         => '#2a7c5b',
            self::ARRIVED            => '#5b21b6',
            self::RESOLVED           => '#15803d',
            self::CANCELLED          => '#64748b',
            self::REOPENED           => '#c2410c',
            self::FIELD_REPORT_FILED => '#1b3d52',
            default                  => '#94a3b8',
        };
    }
}
