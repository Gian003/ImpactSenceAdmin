<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The responding patroller's first-hand account of a scene.
 *
 * Append-only by convention: see the migration for why a correction is a new
 * row rather than an edit.
 */
class IncidentFieldReport extends Model
{
    use HasFactory;

    /** The four IRF fields this report can supply, in IRF order. */
    public const CONDITION_FIELDS = [
        'vehicles_involved',
        'injured_count',
        'road_condition',
        'weather_condition',
    ];

    /** Mirrors the options the IRF form offers, so the two can't drift. */
    public const ROAD_CONDITIONS = ['Dry', 'Wet', 'Icy', 'Under Repair'];

    public const WEATHER_CONDITIONS = ['Clear', 'Cloudy', 'Rainy', 'Foggy', 'Stormy'];

    protected $fillable = [
        'incident_id',
        'patrol_unit_id',
        'narrative',
        'vehicles_involved',
        'injured_count',
        'road_condition',
        'weather_condition',
        'submitted_latitude',
        'submitted_longitude',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'vehicles_involved'   => 'integer',
            'injured_count'       => 'integer',
            'submitted_latitude'  => 'float',
            'submitted_longitude' => 'float',
            'submitted_at'        => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function patrolUnit(): BelongsTo
    {
        return $this->belongsTo(PatrolUnit::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(IncidentFieldPhoto::class);
    }

    /**
     * How far the author was from the recorded scene when they filed, in
     * metres, or null if either end has no coordinates.
     *
     * Shown to the investigator rather than acted on: a report filed from
     * across town isn't necessarily wrong (GPS fails indoors, and a responder
     * may write up after leaving), but it is something the person relying on
     * the account should be able to see.
     */
    public function metresFromScene(): ?float
    {
        $incident = $this->incident;

        if ($this->submitted_latitude === null || $this->submitted_longitude === null
            || $incident?->latitude === null || $incident?->longitude === null) {
            return null;
        }

        $earthRadius = 6371000;
        $latFrom = deg2rad((float) $incident->latitude);
        $latTo   = deg2rad($this->submitted_latitude);
        $dLat    = $latTo - $latFrom;
        $dLon    = deg2rad($this->submitted_longitude - (float) $incident->longitude);

        $a = sin($dLat / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * asin(min(1.0, sqrt($a)));
    }

    /**
     * The narrative as it should appear inside the IRF: attributed, so the
     * investigator signing the form can see the words are the responder's and
     * not the system's.
     */
    public function attributedNarrative(): string
    {
        if (! trim((string) $this->narrative)) {
            return '';
        }

        $author = $this->patrolUnit
            ? $this->patrolUnit->full_name
                . ($this->patrolUnit->badge_number ? " ({$this->patrolUnit->badge_number})" : '')
            : 'the responding unit';

        $when = $this->submitted_at?->format('d F Y \a\t h:i A') ?? 'an unrecorded time';

        return "Field report by {$author}, filed {$when}:\n\n" . trim($this->narrative);
    }
}
