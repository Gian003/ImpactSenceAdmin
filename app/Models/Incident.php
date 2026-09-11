<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use HasFactory;

    /**
     * How far back the TOC alert panel counts an open incident as "live".
     *
     * Anything older is still open, still listed on the Incidents page, and
     * still counted as backlog — it just stops being drawn as an active
     * alert, because a month-old unclosed case and a call from ninety
     * seconds ago are different problems and a dispatch board that mixes
     * them cannot be trusted for either.
     *
     * Widen it for a demo against historical data; tighten it in service.
     */
    public const LIVE_WINDOW_HOURS = 12;

    protected $fillable = [
        'rider_id',
        'device_id',
        'patrol_unit_id',
        'type',
        'latitude',
        'longitude',
        'address',
        'severity',
        'status',
        'notes',
        'vehicles_involved',
        'injured_count',
        'road_condition',
        'weather_condition',
        'twilio_call_sid',
        'dispatched_at',
        'arrived_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'          => 'float',
            'longitude'         => 'float',
            'vehicles_involved' => 'integer',
            'injured_count'     => 'integer',
            'dispatched_at'     => 'datetime',
            'arrived_at'        => 'datetime',
            'resolved_at'       => 'datetime',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function patrolUnit(): BelongsTo
    {
        return $this->belongsTo(PatrolUnit::class);
    }

    /**
     * A factual scaffold for Item D of the IRF, offered to the investigator
     * behind a button rather than filled in silently.
     *
     * Deliberately answers only WHO, WHAT, WHEN and WHERE — the four the
     * system can actually evidence from GPS, timestamps and the device
     * record. WHY and HOW are the investigator's determination, and a
     * system that guesses at cause or fault on a document that becomes
     * evidence is worse than one that leaves the box empty.
     *
     * Every sentence is phrased as what was *recorded*, not as what
     * happened, so the officer is never made to assert something the system
     * merely inferred.
     */
    public function narrativeDraft(): string
    {
        $this->loadMissing(['rider', 'device', 'patrolUnit']);

        $rider = \App\Models\User::cleanName($this->rider?->full_name) ?: 'an unidentified rider';
        $when  = $this->created_at?->format('d F Y \a\t h:i A') ?? 'an unrecorded time';

        $event = match ($this->type) {
            'collision'   => 'a possible collision',
            'fall'        => 'a possible fall',
            'voice_alert' => 'a voice-triggered emergency alert',
            default       => 'a possible incident',
        };

        // How the report reached the system is itself a fact worth stating —
        // it's the difference between a device detecting a crash and a rider
        // raising one from the app.
        $origin = $this->device
            ? "the ImpactSense device {$this->device->device_code} assigned to {$rider} recorded {$event} and transmitted an automatic alert"
            : "{$rider} reported {$event} through the ImpactSense mobile application";

        $lines = ["On {$when}, {$origin}. The system classified the severity as "
            . ucfirst((string) $this->severity) . '.'];

        if ($this->address || ($this->latitude !== null && $this->longitude !== null)) {
            $where = $this->address ? $this->address : 'no resolved address';
            $coords = ($this->latitude !== null && $this->longitude !== null)
                ? ' (' . number_format((float) $this->latitude, 6) . ', ' . number_format((float) $this->longitude, 6) . ')'
                : '';
            $lines[] = "Recorded location: {$where}{$coords}.";
        }

        if ($this->patrolUnit) {
            $dispatched = $this->dispatched_at
                ? ' at ' . $this->dispatched_at->format('h:i A')
                : '';
            $lines[] = "Patrol unit {$this->patrolUnit->full_name} was dispatched{$dispatched}.";
        }

        $lines[] = match ($this->status) {
            'resolved'    => 'The incident was marked resolved'
                . ($this->resolved_at ? ' at ' . $this->resolved_at->format('h:i A') : '') . '.',
            'false_alarm' => 'The alert was subsequently cancelled as a false alarm.',
            'arrived'     => 'The responding unit was recorded as on scene'
                . ($this->arrived_at ? ' at ' . $this->arrived_at->format('h:i A') : '')
                . ', and the incident remained open at the time this record was generated.',
            'dispatched'  => 'The incident was still marked dispatched at the time this record was generated.',
            default       => 'The incident was still marked pending at the time this record was generated.',
        };

        // Only the details someone actually entered — an unfilled figure
        // must not print as a confident zero on an official form.
        $conditions = array_filter([
            $this->vehicles_involved !== null ? "{$this->vehicles_involved} vehicle(s) involved" : null,
            $this->injured_count !== null ? "{$this->injured_count} person(s) injured" : null,
            $this->road_condition ? "road condition {$this->road_condition}" : null,
            $this->weather_condition ? "weather {$this->weather_condition}" : null,
        ]);

        if ($conditions) {
            $lines[] = 'Recorded conditions: ' . implode('; ', $conditions) . '.';
        }

        // The responder's own words go last and stay attributed. Everything
        // above is what the system recorded; this is what a person at the
        // scene said, and the investigator signing the form has to be able to
        // tell the two apart.
        foreach ($this->fieldReports as $report) {
            if ($attributed = $report->attributedNarrative()) {
                $lines[] = $attributed;
            }
        }

        return implode("\n\n", $lines);
    }

    public function incidentRecords(): HasMany
    {
        return $this->hasMany(IncidentRecord::class);
    }

    /** What happened to this incident, oldest first. Append-only. */
    public function events(): HasMany
    {
        return $this->hasMany(IncidentEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    /**
     * First-hand accounts from responding units, oldest first — the order
     * they were filed is part of what they say.
     */
    public function fieldReports(): HasMany
    {
        return $this->hasMany(IncidentFieldReport::class)->orderBy('submitted_at');
    }

    /** The most recent responder account, if any unit has filed one. */
    public function latestFieldReport(): ?IncidentFieldReport
    {
        return $this->fieldReports()->with(['patrolUnit', 'photos'])->get()->last();
    }
}
