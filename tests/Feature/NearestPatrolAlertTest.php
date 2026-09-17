<?php

namespace Tests\Feature;

use App\Jobs\AlertNearestPatrol;
use App\Models\Incident;
use App\Models\IncidentEvent;
use App\Models\PatrolUnit;
use App\Models\User;
use App\Services\FcmService;
use App\Services\GeocodingService;
use App\Services\VoiceCallService;
use App\Support\PatrolAlertSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * The nearest-patrol alert end to end: the job's rules, the calls it places,
 * what it writes to the timeline, and the acceptance endpoint's guard.
 *
 * Runs on an in-memory SQLite database holding only the four tables involved.
 * The project's full migration set cannot run on SQLite — an earlier
 * migration uses MySQL's MODIFY — and these tests must never touch the real
 * database either.
 */
class NearestPatrolAlertTest extends TestCase
{
    private const LAT = 15.9754;
    private const LNG = 120.5697;

    private $voice;
    private $fcm;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.nearest_patrol_test' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => false,
            ],
            'database.default'        => 'nearest_patrol_test',
            'broadcasting.default'    => 'null',
            'queue.default'           => 'database',
            'services.patrol_alert'   => [
                'enabled' => true, 'radius_km' => 5, 'accept_seconds' => 90,
                'max_units' => 3, 'call' => true,
            ],
        ]);
        DB::purge('nearest_patrol_test');
        PatrolAlertSchema::forget();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('full_name')->nullable();
            $t->string('phone_number')->nullable();
            $t->string('fcm_token')->nullable();
            $t->timestamps();
        });

        Schema::create('patrol_units', function (Blueprint $t) {
            $t->id();
            $t->string('full_name');
            $t->string('badge_number')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('rank')->nullable();
            $t->string('mobile_number')->nullable();
            $t->decimal('current_latitude', 10, 7)->nullable();
            $t->decimal('current_longitude', 10, 7)->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->string('status')->default('off_duty');
            $t->boolean('on_duty')->default(false);
            $t->string('fcm_token')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('incidents', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('rider_id')->nullable();
            $t->unsignedBigInteger('device_id')->nullable();
            $t->unsignedBigInteger('patrol_unit_id')->nullable();
            $t->string('type')->default('collision');
            $t->decimal('latitude', 10, 7);
            $t->decimal('longitude', 10, 7);
            $t->boolean('location_verified')->nullable();
            $t->string('address')->nullable();
            $t->string('severity')->default('high');
            $t->string('status')->default('pending');
            $t->text('notes')->nullable();
            $t->string('twilio_call_sid')->nullable();
            $t->timestamp('dispatched_at')->nullable();
            $t->timestamp('arrived_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('incident_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('incident_id');
            $t->string('type', 40);
            $t->string('status_from', 20)->nullable();
            $t->string('status_to', 20)->nullable();
            $t->string('actor_type', 20)->nullable();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('actor_name')->nullable();
            $t->json('payload')->nullable();
            $t->timestamp('occurred_at');
            $t->boolean('reconstructed')->default(false);
            $t->timestamps();
        });

        $this->voice = Mockery::mock(VoiceCallService::class);
        $this->fcm   = Mockery::mock(FcmService::class);
        $this->fcm->allows('sendToToken')->andReturn(true)->byDefault();
        $this->fcm->allows('notifyPatrol')->byDefault();
        $this->fcm->allows('notifyRider')->byDefault();
        $this->app->instance(VoiceCallService::class, $this->voice);
        $this->app->instance(FcmService::class, $this->fcm);

        $geo = Mockery::mock(GeocodingService::class);
        $geo->allows('reverse')->andReturn('Poblacion, Urdaneta City');
        $this->app->instance(GeocodingService::class, $geo);

        Queue::fake();
    }

    private function incident(array $overrides = []): Incident
    {
        $rider = User::forceCreate(['full_name' => 'Juan Dela Cruz', 'phone_number' => '09170000000']);

        return Incident::create(array_merge([
            'rider_id'          => $rider->id,
            'latitude'          => self::LAT,
            'longitude'         => self::LNG,
            'location_verified' => true,
            'severity'          => 'high',
            'status'            => 'pending',
        ], $overrides));
    }

    private function unit(string $name, float $latOffset, array $overrides = []): PatrolUnit
    {
        return PatrolUnit::forceCreate(array_merge([
            'full_name'         => $name,
            'badge_number'      => 'B-' . $name,
            'mobile_number'     => '0917' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'current_latitude'  => self::LAT + $latOffset,
            'current_longitude' => self::LNG,
            'last_seen_at'      => now()->subSeconds(20),
            'status'            => 'available',
            'on_duty'           => true,
            'fcm_token'         => 'token-' . $name,
        ], $overrides));
    }

    private function runJob(Incident $incident, int $round = 1, array $alerted = []): void
    {
        $this->app->call([new AlertNearestPatrol($incident, $round, $alerted), 'handle']);
    }

    private function events(Incident $incident, string $type)
    {
        return IncidentEvent::where('incident_id', $incident->id)->where('type', $type)->get();
    }

    public function test_alerts_the_nearest_unit_by_call_and_push_and_schedules_the_next(): void
    {
        $this->unit('Far', 0.03);
        $near = $this->unit('Near', 0.005, ['mobile_number' => '0917-555-1234']);
        $incident = $this->incident();

        $this->voice->expects('call')
            ->withArgs(fn ($number, $lines) => $number === '+639175551234'
                && str_contains(implode(' ', $lines), 'nearest available unit'))
            ->andReturn('CA123');
        $this->fcm->expects('sendToToken')
            ->withArgs(fn ($token) => $token === 'token-Near')
            ->andReturn(true);

        $this->runJob($incident);

        $alert = $this->events($incident, IncidentEvent::PATROL_ALERTED)->sole();
        $this->assertSame('Near', $alert->payload['patrol_unit']);
        $this->assertEqualsWithDelta(0.56, $alert->payload['distance_km'], 0.01);
        $this->assertSame('app, push, call', $alert->payload['reached_by']);
        $this->assertStringContainsString('Nearest unit Near alerted (0.56 km away)', $alert->describe());

        Queue::assertPushed(AlertNearestPatrol::class, fn ($job) =>
            $job->round === 2 && $job->alreadyAlerted === [$near->id] && $job->delay !== null);

        // An alert is not a dispatch.
        $this->assertNull($incident->fresh()->patrol_unit_id);
        $this->assertSame('pending', $incident->fresh()->status);
    }

    public function test_second_round_alerts_the_next_nearest_unit(): void
    {
        $near = $this->unit('Near', 0.005);
        $this->unit('Next', 0.02);
        $incident = $this->incident();

        $this->voice->expects('call')->andReturn('CA2');

        $this->runJob($incident, 2, [$near->id]);

        $this->assertSame('Next', $this->events($incident, IncidentEvent::PATROL_ALERTED)->sole()->payload['patrol_unit']);
    }

    public function test_does_not_run_when_the_location_is_not_confirmed(): void
    {
        $this->unit('Near', 0.005);
        $noFix   = $this->incident(['location_verified' => false]);
        $unknown = $this->incident(['location_verified' => null]);

        $this->voice->expects('call')->never();

        $this->runJob($noFix);
        $this->runJob($unknown);

        $this->assertStringContainsString('no GPS fix',
            $this->events($noFix, IncidentEvent::PATROL_ALERT_ENDED)->sole()->payload['reason']);
        $this->assertStringContainsString('did not confirm',
            $this->events($unknown, IncidentEvent::PATROL_ALERT_ENDED)->sole()->payload['reason']);
        $this->assertCount(0, IncidentEvent::where('type', IncidentEvent::PATROL_ALERTED)->get());
    }

    public function test_stops_quietly_once_someone_has_the_call(): void
    {
        $taker = $this->unit('Taker', 0.01, ['status' => 'dispatched']);
        $this->unit('Other', 0.005);
        $incident = $this->incident(['patrol_unit_id' => $taker->id, 'status' => 'dispatched']);

        $this->voice->expects('call')->never();

        $this->runJob($incident, 2, [$taker->id]);

        $this->assertCount(0, IncidentEvent::all());
        Queue::assertNotPushed(AlertNearestPatrol::class);
    }

    public function test_records_when_no_unit_is_in_range(): void
    {
        $this->unit('Distant', 0.2);
        $incident = $this->incident();

        $this->voice->expects('call')->never();

        $this->runJob($incident);

        $this->assertSame('no on-duty unit within 5 km. The TOC will dispatch.',
            $this->events($incident, IncidentEvent::PATROL_ALERT_ENDED)->sole()->payload['reason']);
    }

    public function test_records_when_nobody_accepted_after_the_last_unit(): void
    {
        $a = $this->unit('A', 0.005);
        $b = $this->unit('B', 0.01);
        $c = $this->unit('C', 0.015);
        $incident = $this->incident();

        $this->voice->expects('call')->never();

        $this->runJob($incident, 4, [$a->id, $b->id, $c->id]);

        $this->assertSame('no unit accepted after 3 alerts. The TOC will dispatch.',
            $this->events($incident, IncidentEvent::PATROL_ALERT_ENDED)->sole()->payload['reason']);
    }

    public function test_on_the_sync_queue_only_the_nearest_unit_is_alerted(): void
    {
        config(['queue.default' => 'sync']);
        $this->unit('Near', 0.005);
        $this->unit('Next', 0.02);
        $incident = $this->incident();

        $this->voice->expects('call')->once()->andReturn('CA1');

        $this->runJob($incident);

        Queue::assertNotPushed(AlertNearestPatrol::class);
        $this->assertStringContainsString('queue runs inline',
            $this->events($incident, IncidentEvent::PATROL_ALERTED)->sole()->payload['follow_up']);
    }

    public function test_a_unit_with_no_usable_number_still_gets_the_push(): void
    {
        $this->unit('NoPhone', 0.005, ['mobile_number' => null]);
        $incident = $this->incident();

        $this->voice->expects('call')->never();

        $this->runJob($incident);

        $this->assertSame('app, push',
            $this->events($incident, IncidentEvent::PATROL_ALERTED)->sole()->payload['reached_by']);
    }

    public function test_a_second_unit_cannot_take_a_call_that_is_already_taken(): void
    {
        $first  = $this->unit('First', 0.005, ['status' => 'dispatched']);
        $second = $this->unit('Second', 0.01);
        $incident = $this->incident(['patrol_unit_id' => $first->id, 'status' => 'dispatched']);

        Sanctum::actingAs($second, ['*']);

        $this->patchJson("/api/patrol/incidents/{$incident->id}/status", ['status' => 'dispatched'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Already taken by First. The TOC can reassign it if needed.');

        $this->assertSame('available', $second->fresh()->status);
        $this->assertSame($first->id, $incident->fresh()->patrol_unit_id);
    }

    public function test_the_first_unit_to_accept_gets_the_call_and_is_free_again_after(): void
    {
        $unit = $this->unit('Responder', 0.005);
        $incident = $this->incident();

        Sanctum::actingAs($unit, ['*']);

        $this->patchJson("/api/patrol/incidents/{$incident->id}/status", ['status' => 'dispatched'])
            ->assertOk();
        $this->assertSame($unit->id, $incident->fresh()->patrol_unit_id);
        $this->assertSame('dispatched', $unit->fresh()->status);

        $this->patchJson("/api/patrol/incidents/{$incident->id}/status", ['status' => 'resolved'])
            ->assertOk();

        // Still on shift, so back in the pool — it used to drop to off_duty.
        $this->assertSame('available', $unit->fresh()->status);
    }

    public function test_the_duty_switch_sets_both_flags(): void
    {
        $unit = $this->unit('Officer', 0.005, ['on_duty' => false, 'status' => 'off_duty']);
        Sanctum::actingAs($unit, ['*']);

        $this->postJson('/api/patrol/duty', ['on_duty' => true])
            ->assertOk()
            ->assertJsonPath('data.on_duty', true)
            ->assertJsonPath('data.status', 'available');

        $this->postJson('/api/patrol/duty', ['on_duty' => false])
            ->assertOk()
            ->assertJsonPath('data.on_duty', false)
            ->assertJsonPath('data.status', 'off_duty');
    }

    public function test_going_off_duty_mid_call_keeps_the_unit_on_that_call(): void
    {
        $unit = $this->unit('Busy', 0.005, ['status' => 'dispatched']);
        Sanctum::actingAs($unit, ['*']);

        $this->postJson('/api/patrol/duty', ['on_duty' => false])
            ->assertOk()
            ->assertJsonPath('data.status', 'dispatched');
    }
}
