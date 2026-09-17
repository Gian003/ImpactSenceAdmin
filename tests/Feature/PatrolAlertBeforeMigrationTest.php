<?php

namespace Tests\Feature;

use App\Jobs\AlertNearestPatrol;
use App\Models\Device;
use App\Models\Incident;
use App\Models\PatrolUnit;
use App\Models\User;
use App\Support\PatrolAlertSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The code can reach a server before its migration does. On a database
 * without incidents.location_verified and patrol_units.on_duty, a crash report
 * must still be saved — that insert is the one that can never fail — and the
 * rest of the feature must stay out of the way rather than error.
 */
class PatrolAlertBeforeMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.before_migration_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default'     => 'before_migration_test',
            'broadcasting.default' => 'null',
            'services.patrol_alert.enabled' => true,
        ]);
        DB::purge('before_migration_test');
        PatrolAlertSchema::forget();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('full_name')->nullable();
            $t->string('fcm_token')->nullable();
            $t->timestamps();
        });
        Schema::create('devices', function (Blueprint $t) {
            $t->id();
            $t->string('device_code')->unique();
            $t->unsignedBigInteger('rider_id')->nullable();
            $t->string('pairing_key')->nullable();
            $t->timestamps();
        });
        // As they are today: no location_verified, no on_duty.
        Schema::create('incidents', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('rider_id')->nullable();
            $t->unsignedBigInteger('device_id')->nullable();
            $t->unsignedBigInteger('patrol_unit_id')->nullable();
            $t->string('type')->default('collision');
            $t->decimal('latitude', 10, 7);
            $t->decimal('longitude', 10, 7);
            $t->string('address')->nullable();
            $t->string('severity')->default('high');
            $t->string('status')->default('pending');
            $t->timestamps();
        });
        Schema::create('patrol_units', function (Blueprint $t) {
            $t->id();
            $t->string('full_name');
            $t->string('status')->default('off_duty');
            $t->timestamp('last_seen_at')->nullable();
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

        Queue::fake();
    }

    protected function tearDown(): void
    {
        PatrolAlertSchema::forget();
        parent::tearDown();
    }

    public function test_a_crash_report_from_new_firmware_is_still_saved(): void
    {
        $rider = User::forceCreate(['full_name' => 'Juan Dela Cruz']);
        Device::forceCreate(['device_code' => 'IMP-001', 'rider_id' => $rider->id]);

        $this->postJson('/api/device/incident', [
            'device_code'       => 'IMP-001',
            'latitude'          => 15.9754,
            'longitude'         => 120.5697,
            'location_verified' => true,
        ])->assertCreated();

        $this->assertSame(1, Incident::count());
        Queue::assertNotPushed(AlertNearestPatrol::class);
    }

    public function test_the_duty_switch_refuses_cleanly(): void
    {
        $unit = PatrolUnit::forceCreate(['full_name' => 'Officer']);
        Sanctum::actingAs($unit, ['*']);

        $this->postJson('/api/patrol/duty', ['on_duty' => true])
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }

    public function test_logging_out_still_works(): void
    {
        $unit = PatrolUnit::forceCreate(['full_name' => 'Officer', 'status' => 'available']);

        // Sanctum::actingAs gives a transient token whose delete() is a no-op,
        // which is exactly what this needs: only the status write is under test.
        Sanctum::actingAs($unit, ['*']);

        $this->postJson('/api/patrol/logout')->assertOk();
        $this->assertSame('off_duty', $unit->fresh()->status);
    }
}
