<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpeedReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpeedSampleController extends Controller
{
    /** One flush carries at most this many samples. */
    private const MAX_BATCH = 120;

    /**
     * A phone below this is walking, standing at a light, or drifting on GPS
     * noise. Folding those into a road's average drags it down and makes a
     * genuinely fast street look compliant.
     */
    private const MIN_SPEED_KPH = 5;

    /** Above this the fix is almost certainly bad rather than the rider fast. */
    private const MAX_SPEED_KPH = 200;

    /**
     * Receives a batch of GPS speed samples from a rider's phone.
     *
     * Samples are stored with no identity attached — no rider_id, no
     * device_id. A zone's 85th-percentile speed does not require knowing who
     * was riding, and the alternative is a continuous movement log of private
     * citizens sitting in a police database. That is a deliberate limit: this
     * data can say "this street runs 12kph over its limit", and it
     * structurally cannot say "this rider was speeding".
     *
     * Batched because the alternative is a request per fix — at one fix per
     * second that is 3,600 requests an hour per rider, over mobile data, for
     * a figure nobody reads in real time.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'samples'                => ['required', 'array', 'min:1', 'max:' . self::MAX_BATCH],
            'samples.*.latitude'     => ['required', 'numeric', 'between:-90,90'],
            'samples.*.longitude'    => ['required', 'numeric', 'between:-180,180'],
            'samples.*.speed_kph'    => ['required', 'numeric', 'min:0', 'max:' . self::MAX_SPEED_KPH],
            'samples.*.recorded_at'  => ['nullable', 'date'],
        ]);

        $now  = now();
        $rows = [];

        foreach ($data['samples'] as $sample) {
            $speed = (int) round($sample['speed_kph']);

            if ($speed < self::MIN_SPEED_KPH) {
                continue;
            }

            // Trusted only as far as "not in the future and not absurdly old" —
            // a phone that was offline in a dead spot may flush an hour late,
            // but its clock is still the rider's to set.
            $recordedAt = isset($sample['recorded_at'])
                ? \Illuminate\Support\Carbon::parse($sample['recorded_at'])
                : $now;

            if ($recordedAt->greaterThan($now) || $recordedAt->lessThan($now->copy()->subDay())) {
                $recordedAt = $now;
            }

            $rows[] = [
                'device_id'  => null,
                'latitude'   => $sample['latitude'],
                'longitude'  => $sample['longitude'],
                'speed_kph'  => $speed,
                'created_at' => $recordedAt,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            // One insert for the batch — this is the hot path if the feature
            // is ever switched on for a real fleet.
            SpeedReport::insert($rows);
        }

        return $this->apiResponse(true, 'Speed samples recorded', [
            'accepted' => count($rows),
            'discarded' => count($data['samples']) - count($rows),
        ]);
    }
}
