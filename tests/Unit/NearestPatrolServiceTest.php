<?php

namespace Tests\Unit;

use App\Models\PatrolUnit;
use App\Services\NearestPatrolService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The selection rules for the nearest-patrol alert. No database: rank() takes
 * the units it is given, so each rule can be pinned down on its own.
 */
class NearestPatrolServiceTest extends TestCase
{
    // Urdaneta City Hall, roughly. The crash in every test.
    private const LAT = 15.9754;
    private const LNG = 120.5697;

    private function unit(int $id, float $lat, float $lng, array $overrides = []): PatrolUnit
    {
        $unit = new PatrolUnit();
        $unit->forceFill(array_merge([
            'id'                => $id,
            'full_name'         => "Unit {$id}",
            'on_duty'           => true,
            'status'            => 'available',
            'last_seen_at'      => now()->subSeconds(20),
            'current_latitude'  => $lat,
            'current_longitude' => $lng,
        ], $overrides));

        return $unit;
    }

    public function test_distance_matches_a_known_value(): void
    {
        // One hundredth of a degree of latitude is about 1.11 km anywhere.
        $km = NearestPatrolService::distanceKm(self::LAT, self::LNG, self::LAT + 0.01, self::LNG);

        $this->assertEqualsWithDelta(1.112, $km, 0.005);
    }

    public function test_picks_the_closest_free_unit_first(): void
    {
        $far  = $this->unit(1, self::LAT + 0.03, self::LNG);   // ~3.3 km
        $near = $this->unit(2, self::LAT + 0.005, self::LNG);  // ~0.6 km
        $mid  = $this->unit(3, self::LAT + 0.015, self::LNG);  // ~1.7 km

        $ranked = NearestPatrolService::rank([$far, $near, $mid], self::LAT, self::LNG, 5);

        $this->assertSame([2, 3, 1], array_map(fn ($r) => $r['unit']->id, $ranked));
        $this->assertEqualsWithDelta(0.56, $ranked[0]['km'], 0.01);
    }

    public function test_leaves_out_units_beyond_the_radius(): void
    {
        $inside  = $this->unit(1, self::LAT + 0.02, self::LNG);  // ~2.2 km
        $outside = $this->unit(2, self::LAT + 0.08, self::LNG);  // ~8.9 km

        $ranked = NearestPatrolService::rank([$inside, $outside], self::LAT, self::LNG, 5);

        $this->assertSame([1], array_map(fn ($r) => $r['unit']->id, $ranked));
    }

    public function test_never_alerts_an_officer_who_is_off_duty(): void
    {
        // The closest unit, but its officer has not switched on duty. This is
        // the "went home with the app open" case the on_duty flag exists for.
        $offDuty = $this->unit(1, self::LAT + 0.001, self::LNG, ['on_duty' => false, 'status' => 'off_duty']);
        $onDuty  = $this->unit(2, self::LAT + 0.02, self::LNG);

        $ranked = NearestPatrolService::rank([$offDuty, $onDuty], self::LAT, self::LNG, 5);

        $this->assertSame([2], array_map(fn ($r) => $r['unit']->id, $ranked));
    }

    public function test_skips_a_unit_already_on_a_call(): void
    {
        $busy = $this->unit(1, self::LAT + 0.001, self::LNG, ['status' => 'dispatched']);
        $free = $this->unit(2, self::LAT + 0.02, self::LNG);

        $ranked = NearestPatrolService::rank([$busy, $free], self::LAT, self::LNG, 5);

        $this->assertSame([2], array_map(fn ($r) => $r['unit']->id, $ranked));
    }

    public function test_skips_a_unit_whose_app_has_stopped_reporting(): void
    {
        // Its last position is five minutes old, so it is not where it says.
        $stale = $this->unit(1, self::LAT + 0.001, self::LNG, ['last_seen_at' => now()->subMinutes(5)]);
        $live  = $this->unit(2, self::LAT + 0.02, self::LNG);

        $ranked = NearestPatrolService::rank([$stale, $live], self::LAT, self::LNG, 5);

        $this->assertSame([2], array_map(fn ($r) => $r['unit']->id, $ranked));
    }

    public function test_skips_a_unit_with_no_position(): void
    {
        $unknown = $this->unit(1, 0, 0, ['current_latitude' => null, 'current_longitude' => null]);

        $this->assertSame([], NearestPatrolService::rank([$unknown], self::LAT, self::LNG, 5));
    }

    public function test_does_not_alert_the_same_unit_twice(): void
    {
        $alreadyAlerted = $this->unit(1, self::LAT + 0.001, self::LNG);
        $next           = $this->unit(2, self::LAT + 0.02, self::LNG);

        $ranked = NearestPatrolService::rank([$alreadyAlerted, $next], self::LAT, self::LNG, 5, [1]);

        $this->assertSame([2], array_map(fn ($r) => $r['unit']->id, $ranked));
    }

    public function test_finishing_a_call_returns_an_on_duty_unit_to_available(): void
    {
        $this->assertSame('available', $this->unit(1, self::LAT, self::LNG)->idleStatus());
        $this->assertSame('off_duty', $this->unit(2, self::LAT, self::LNG, ['on_duty' => false])->idleStatus());
    }

    #[DataProvider('phoneNumbers')]
    public function test_normalises_philippine_mobile_numbers(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, NearestPatrolService::toE164($input));
    }

    public static function phoneNumbers(): array
    {
        return [
            'local 09 form'         => ['09171234567', '+639171234567'],
            'with spaces'           => ['0917 123 4567', '+639171234567'],
            'with dashes'           => ['0917-123-4567', '+639171234567'],
            'already +63'           => ['+639171234567', '+639171234567'],
            '63 without plus'       => ['639171234567', '+639171234567'],
            'missing leading zero'  => ['9171234567', '+639171234567'],
            'landline is refused'   => ['(075) 568 2345', null],
            'too short is refused'  => ['0917123', null],
            'empty'                 => ['', null],
            'null'                  => [null, null],
        ];
    }
}
