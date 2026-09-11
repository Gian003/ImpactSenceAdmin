<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class SpeedZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'path',
        'radius_meters',
        'speed_limit_kph',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude'  => 'decimal:7',
            'longitude' => 'decimal:7',
            'path'      => 'array',
        ];
    }

    /**
     * How far back a zone's figures look. Averaging over all time is not a
     * "live" average — once a zone has a few months of history behind it the
     * number stops moving, and a street that got worse last week looks fine.
     */
    public const WINDOW_DAYS = 7;

    /**
     * The share of samples at or below the posted limit that traffic
     * engineering treats as normal. The 85th percentile is the standard
     * measure precisely because a mean hides the problem: a zone where most
     * riders do 30 and a handful do 90 averages out comfortably legal.
     */
    public const PERCENTILE = 85;

    /**
     * A zone is also worth flagging when a meaningful minority is speeding,
     * even if the typical rider is not.
     *
     * The 85th percentile on its own only sees a tail once it exceeds 15% of
     * traffic: a street where nine riders in ten sit at 30 in a 40 zone and
     * the tenth does 85 reads as compliant on both the mean AND the
     * percentile, while being exactly the street where someone gets killed.
     * Ten percent over the posted limit is worth an officer's attention.
     */
    public const VIOLATION_RATE_FLAG = 10;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TocPersonnel::class, 'created_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(SpeedReport::class);
    }

    /**
     * Speed figures for a set of zones, keyed by zone id.
     *
     * Replaces a SpeedReport::all() that pulled the whole table into memory
     * and ran a PHP haversine for every sample against every zone. That is
     * O(zones x samples) and was measured at two seconds of pure CPU for
     * 100k samples across 20 zones — on a table that grows by ~17k rows per
     * rider per day, on a query that runs on the live dispatch board.
     *
     * Now each zone asks the database only for samples inside its bounding
     * box within the window, which the (latitude, longitude) and created_at
     * indexes can both serve. The exact circle test stays in PHP — a square
     * around a circle over-selects by about 27%, so the refine runs over a
     * handful of rows instead of the table, and the arithmetic stays
     * identical on every database engine.
     */
    public static function statsFor(iterable $zones, int $windowDays = self::WINDOW_DAYS): Collection
    {
        $since = now()->subDays($windowDays);

        return collect($zones)->mapWithKeys(fn (self $zone) => [
            $zone->id => $zone->stats($since),
        ]);
    }

    /**
     * @return object{avg_speed:?int, percentile_speed:?int, max_speed:?int,
     *                sample_count:int, violation_count:int, violation_rate:?int,
     *                is_violating:bool, flag_reason:?string, window_days:int}
     */
    public function stats(?\DateTimeInterface $since = null, int $windowDays = self::WINDOW_DAYS): object
    {
        $since ??= now()->subDays($windowDays);

        // The box spans the whole centreline plus the corridor half-width, so
        // the index does the coarse work and the exact test only runs over
        // what is already nearby.
        [$minLat, $maxLat, $minLng, $maxLng] = $this->boundingBox();

        $candidates = SpeedReport::query()
            ->where('created_at', '>=', $since)
            ->whereBetween('latitude',  [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->get(['latitude', 'longitude', 'speed_kph']);

        $speeds = $candidates
            ->filter(fn ($s) => $this->containsPoint(
                (float) $s->latitude, (float) $s->longitude
            ))
            ->pluck('speed_kph')
            ->map(fn ($v) => (int) $v)
            ->sort()
            ->values();

        $count = $speeds->count();

        if ($count === 0) {
            return (object) [
                'avg_speed'        => null,
                'percentile_speed' => null,
                'max_speed'        => null,
                'sample_count'     => 0,
                'violation_count'  => 0,
                'violation_rate'   => null,
                'is_violating'     => false,
                'flag_reason'      => null,
                'window_days'      => $windowDays,
            ];
        }

        $violations = $speeds->filter(fn ($v) => $v > $this->speed_limit_kph)->count();
        $percentile = self::percentile($speeds->all(), self::PERCENTILE);
        $rate       = (int) round($violations / $count * 100);

        // Two different problems, and an operator needs to tell them apart.
        // "typical" means the street as a whole runs over its limit; "tail"
        // means most riders comply but a persistent minority does not. The
        // second is invisible to both the mean and the percentile.
        $overTypical = $percentile > $this->speed_limit_kph;
        $overTail    = $rate >= self::VIOLATION_RATE_FLAG;

        return (object) [
            'avg_speed'        => (int) round($speeds->avg()),
            'percentile_speed' => $percentile,
            'max_speed'        => $speeds->last(),
            'sample_count'     => $count,
            'violation_count'  => $violations,
            'violation_rate'   => $rate,
            'is_violating'     => $overTypical || $overTail,
            'flag_reason'      => $overTypical ? 'typical' : ($overTail ? 'tail' : null),
            'window_days'      => $windowDays,
        ];
    }

    /**
     * The corridor centreline as a list of [lat, lng] pairs.
     *
     * Falls back to the stored centre so a zone with no path — one written
     * before corridors existed, or by anything that bypasses this model —
     * still behaves as the circle it used to be rather than vanishing.
     */
    public function pathPoints(): array
    {
        $points = collect($this->path ?? [])
            ->map(fn ($p) => [(float) ($p['lat'] ?? 0), (float) ($p['lng'] ?? 0)])
            ->all();

        return $points ?: [[(float) $this->latitude, (float) $this->longitude]];
    }

    /** Metres of road this corridor covers. Zero for a single-point zone. */
    public function pathLengthMeters(): float
    {
        $points = $this->pathPoints();
        $total  = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            $total += self::distanceMeters(
                $points[$i - 1][0], $points[$i - 1][1], $points[$i][0], $points[$i][1]
            );
        }

        return $total;
    }

    /**
     * Shortest distance from a point to this corridor's centreline, in metres.
     *
     * With one path point this is distance to a centre — i.e. a circle — which
     * is why zones drawn before corridors existed keep working untouched.
     */
    public function distanceToPathMeters(float $lat, float $lng): float
    {
        $points = $this->pathPoints();

        if (count($points) === 1) {
            return self::distanceMeters($lat, $lng, $points[0][0], $points[0][1]);
        }

        $min = INF;
        for ($i = 1; $i < count($points); $i++) {
            $min = min($min, self::pointToSegmentMeters($lat, $lng, $points[$i - 1], $points[$i]));
        }

        return $min;
    }

    public function containsPoint(float $lat, float $lng): bool
    {
        return $this->distanceToPathMeters($lat, $lng) <= $this->radius_meters;
    }

    /**
     * Distance from a point to a line segment, in metres.
     *
     * Works in a local plane anchored at the point itself: over the few
     * hundred metres a speed zone spans, the error from ignoring the earth's
     * curvature is centimetres, and the alternative is spherical trigonometry
     * nobody can check by reading it.
     *
     * @param array{0:float,1:float} $a segment start as [lat, lng]
     * @param array{0:float,1:float} $b segment end as [lat, lng]
     */
    public static function pointToSegmentMeters(float $lat, float $lng, array $a, array $b): float
    {
        $mPerDegLat = 111320.0;
        $mPerDegLng = 111320.0 * cos(deg2rad($lat));

        // The query point sits at the origin, so the answer is just the
        // length of the closest vector.
        $ax = ($a[1] - $lng) * $mPerDegLng;
        $ay = ($a[0] - $lat) * $mPerDegLat;
        $bx = ($b[1] - $lng) * $mPerDegLng;
        $by = ($b[0] - $lat) * $mPerDegLat;

        $dx = $bx - $ax;
        $dy = $by - $ay;
        $lengthSquared = $dx * $dx + $dy * $dy;

        if ($lengthSquared <= 0.0) {
            return sqrt($ax * $ax + $ay * $ay);
        }

        // Clamped so the closest point stays on the segment rather than
        // running off down the infinite line it lies on.
        $t = max(0.0, min(1.0, -($ax * $dx + $ay * $dy) / $lengthSquared));

        $cx = $ax + $t * $dx;
        $cy = $ay + $t * $dy;

        return sqrt($cx * $cx + $cy * $cy);
    }

    /**
     * Bounding box that contains the whole corridor plus its half-width.
     *
     * @return array{0:float,1:float,2:float,3:float} [minLat, maxLat, minLng, maxLng]
     */
    public function boundingBox(): array
    {
        $points = $this->pathPoints();
        $lats   = array_column($points, 0);
        $lngs   = array_column($points, 1);

        $latDelta = $this->radius_meters / 111320;
        $midLat   = (min($lats) + max($lats)) / 2;
        $lngDelta = $this->radius_meters / (111320 * max(cos(deg2rad($midLat)), 1e-6));

        return [
            min($lats) - $latDelta, max($lats) + $latDelta,
            min($lngs) - $lngDelta, max($lngs) + $lngDelta,
        ];
    }

    /**
     * Shortest distance between two corridors' centrelines, in metres.
     *
     * Checks every segment pair rather than only vertices: two roads can
     * cross at a junction with no vertex of either lying near the other, and
     * treating that as "far apart" is exactly the overlap worth catching.
     */
    public function distanceToZoneMeters(self $other): float
    {
        $mine   = $this->pathPoints();
        $theirs = $other->pathPoints();

        $min = INF;

        foreach ($this->segments($mine) as [$a1, $a2]) {
            foreach ($this->segments($theirs) as [$b1, $b2]) {
                $min = min($min, self::segmentToSegmentMeters($a1, $a2, $b1, $b2));
            }
        }

        return $min;
    }

    /**
     * Consecutive point pairs. A single-point path yields one degenerate
     * segment so the same code handles circles without a special case.
     *
     * @return array<int, array{0:array,1:array}>
     */
    private function segments(array $points): array
    {
        if (count($points) === 1) {
            return [[$points[0], $points[0]]];
        }

        $segments = [];
        for ($i = 1; $i < count($points); $i++) {
            $segments[] = [$points[$i - 1], $points[$i]];
        }

        return $segments;
    }

    /** Shortest distance between two segments, in metres — zero if they cross. */
    public static function segmentToSegmentMeters(array $a1, array $a2, array $b1, array $b2): float
    {
        if (self::segmentsIntersect($a1, $a2, $b1, $b2)) {
            return 0.0;
        }

        return min(
            self::pointToSegmentMeters($a1[0], $a1[1], $b1, $b2),
            self::pointToSegmentMeters($a2[0], $a2[1], $b1, $b2),
            self::pointToSegmentMeters($b1[0], $b1[1], $a1, $a2),
            self::pointToSegmentMeters($b2[0], $b2[1], $a1, $a2),
        );
    }

    /** Standard orientation test, in degrees — scale-free, so no projection. */
    private static function segmentsIntersect(array $p1, array $p2, array $p3, array $p4): bool
    {
        $orient = function (array $a, array $b, array $c): int {
            $v = ($b[1] - $a[1]) * ($c[0] - $a[0]) - ($b[0] - $a[0]) * ($c[1] - $a[1]);

            return abs($v) < 1e-12 ? 0 : ($v > 0 ? 1 : -1);
        };

        $o1 = $orient($p1, $p2, $p3);
        $o2 = $orient($p1, $p2, $p4);
        $o3 = $orient($p3, $p4, $p1);
        $o4 = $orient($p3, $p4, $p2);

        return $o1 !== $o2 && $o3 !== $o4;
    }

    /**
     * The first existing zone whose corridor overlaps the one described, or null.
     *
     * Overlapping zones quietly corrupt both of them: a sample in the shared
     * area counts toward each zone's average, and an operator looking at a
     * flagged street cannot tell which posted limit the reading was judged
     * against. Two corridors overlap when the shortest distance between their
     * centrelines is less than the sum of their half-widths.
     *
     * Unlike the circle version this replaced, corridors following the same
     * road no longer conflict with each other merely for being adjacent —
     * covering a long road took a chain of overlapping circles, every one of
     * which tripped this guard.
     *
     * @param array $path     proposed centreline as [{lat, lng}, ...]
     * @param int|null $ignoreId zone being edited, which cannot conflict with itself
     */
    public static function overlapping(
        array $path,
        int $radiusMeters,
        ?int $ignoreId = null
    ): ?self {
        // An unsaved stand-in so the same corridor maths serves a zone being
        // proposed and one already stored.
        $candidate = new self([
            'path'          => $path,
            'radius_meters' => $radiusMeters,
        ]);

        return self::query()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->get()
            ->first(fn (self $zone) => $candidate->distanceToZoneMeters($zone)
                < ($radiusMeters + $zone->radius_meters));
    }

    /**
     * Nearest-rank percentile over an already-sorted list of values.
     */
    public static function percentile(array $sorted, int $percentile): ?int
    {
        $n = count($sorted);
        if ($n === 0) {
            return null;
        }

        $rank = (int) ceil($percentile / 100 * $n);

        return (int) $sorted[max(0, min($n - 1, $rank - 1))];
    }

    /**
     * Great-circle distance between two coordinates, in meters.
     * Used to test whether a speed_reports sample falls inside this zone's
     * radius — done in PHP rather than raw SQL so it works identically on
     * MySQL (production) and SQLite (tests) without vendor-specific trig.
     */
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMeters = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMeters * $c;
    }
}
