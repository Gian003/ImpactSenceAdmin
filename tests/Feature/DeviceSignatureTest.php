<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Incident;
use App\Models\User;
use App\Support\DeviceSignature;
use App\Support\PatrolAlertSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A report has to prove it came from the device it claims to be from.
 *
 * The device_code alone cannot do that — it is printed on the case, shown on
 * the TOC screen, and sent with every request. These cover the signature that
 * replaces it, and the upgrade path that lets a fleet already in the field
 * keep working until each unit is provisioned.
 */
class DeviceSignatureTest extends TestCase
{
    private const LAT = 15.9754;
    private const LNG = 120.5697;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.signature_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default'              => 'signature_test',
            'broadcasting.default'          => 'null',
            'services.patrol_alert.enabled' => false,
        ]);
        DB::purge('signature_test');
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
            $t->string('signing_secret', 64)->nullable();
            $t->unsignedBigInteger('last_signature_counter')->default(0);
            $t->timestamp('signature_required_since')->nullable();
            $t->string('sim_phone_number')->nullable();
            $t->timestamps();
        });
        Schema::create('toc_personnel', function (Blueprint $t) {
            $t->id();
            $t->string('full_name');
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('emergency_contacts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('rider_id');
            $t->string('name');
            $t->string('phone_number');
            $t->string('relationship')->nullable();
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

    private function device(): Device
    {
        $rider = User::forceCreate(['full_name' => 'Juan Dela Cruz']);

        // Created through the model, so the secret is generated the way a real
        // registration generates it.
        return Device::create(['device_code' => 'IMP-001', 'rider_id' => $rider->id]);
    }

    /** @return array{0: string, 1: array<string, string>} the body and its headers */
    private function signedCrash(Device $device, int $counter, array $overrides = []): array
    {
        $body = json_encode(array_merge([
            'device_code' => $device->device_code,
            'latitude'    => self::LAT,
            'longitude'   => self::LNG,
        ], $overrides));

        return [$body, [
            DeviceSignature::HEADER_COUNTER   => (string) $counter,
            DeviceSignature::HEADER_SIGNATURE => DeviceSignature::compute(
                $device->signing_secret, $device->device_code, (string) $counter, $body,
            ),
            'Content-Type' => 'application/json',
        ]];
    }

    private function sendSigned(string $body, array $headers)
    {
        return $this->call('POST', '/api/device/incident', [], [], [], $this->transformHeadersToServerVars($headers), $body);
    }

    public function test_a_secret_is_generated_at_registration_and_never_serialised(): void
    {
        $device = $this->device();

        $this->assertSame(64, strlen($device->signing_secret), '256 bits of hex');
        $this->assertArrayNotHasKey('signing_secret', $device->toArray(), 'never leaves in a response');
        $this->assertStringStartsWith('SECRET ', $device->provisioningCommand());
    }

    public function test_a_correctly_signed_report_is_accepted(): void
    {
        $device = $this->device();
        [$body, $headers] = $this->signedCrash($device, 1001);

        $this->sendSigned($body, $headers)->assertCreated();

        $this->assertSame(1, Incident::count());
        $device->refresh();
        $this->assertSame('1001', (string) $device->last_signature_counter);
        $this->assertNotNull($device->signature_required_since, 'signing is now mandatory for this device');
    }

    public function test_a_wrong_signature_is_refused(): void
    {
        $device = $this->device();
        [$body, $headers] = $this->signedCrash($device, 1001);
        $headers[DeviceSignature::HEADER_SIGNATURE] = str_repeat('a', 64);

        $this->sendSigned($body, $headers)->assertStatus(401);

        $this->assertSame(0, Incident::count());
    }

    public function test_an_edited_body_no_longer_matches_its_signature(): void
    {
        $device = $this->device();
        [$body, $headers] = $this->signedCrash($device, 1001);

        // The crash moved 30 km. Everything else about the request is genuine.
        $tampered = str_replace('120.5697', '120.9999', $body);

        $this->sendSigned($tampered, $headers)->assertStatus(401);
        $this->assertSame(0, Incident::count());
    }

    public function test_a_captured_report_cannot_be_sent_again(): void
    {
        $device = $this->device();
        [$body, $headers] = $this->signedCrash($device, 1001);

        $this->sendSigned($body, $headers)->assertCreated();

        // Byte for byte the same request, exactly as an eavesdropper would
        // have it. The counter has been used, so it goes no further.
        $this->sendSigned($body, $headers)->assertStatus(401);

        $this->assertSame(1, Incident::count(), 'one crash, not two');
    }

    public function test_an_older_counter_is_refused(): void
    {
        $device = $this->device();

        [$body, $headers] = $this->signedCrash($device, 5000);
        $this->sendSigned($body, $headers)->assertCreated();

        [$oldBody, $oldHeaders] = $this->signedCrash($device, 4999);
        $this->sendSigned($oldBody, $oldHeaders)->assertStatus(401);

        $this->assertSame(1, Incident::count());
    }

    public function test_a_device_that_has_never_signed_still_reports(): void
    {
        // The fleet already in the field: no secret provisioned yet, so it
        // keeps working exactly as before rather than going silent.
        $device = $this->device();

        $this->postJson('/api/device/incident', [
            'device_code' => $device->device_code,
            'latitude'    => self::LAT,
            'longitude'   => self::LNG,
        ])->assertCreated();

        $this->assertNull($device->fresh()->signature_required_since);
    }

    public function test_once_a_device_has_signed_unsigned_reports_are_refused(): void
    {
        $device = $this->device();

        [$body, $headers] = $this->signedCrash($device, 1001);
        $this->sendSigned($body, $headers)->assertCreated();

        // Stripping the headers must not get the old behaviour back.
        $this->postJson('/api/device/incident', [
            'device_code' => $device->device_code,
            'latitude'    => self::LAT,
            'longitude'   => self::LNG,
        ])->assertStatus(401);

        $this->assertSame(1, Incident::count());
    }

    public function test_a_stolen_device_code_alone_proves_nothing(): void
    {
        $device = $this->device();

        // Someone who read the code off the case and signed with a guess.
        [$body, $headers] = $this->signedCrash($device, 1001);
        $headers[DeviceSignature::HEADER_SIGNATURE] = DeviceSignature::compute(
            'a-secret-they-made-up', $device->device_code, '1001', $body,
        );

        $this->sendSigned($body, $headers)->assertStatus(401);
        $this->assertSame(0, Incident::count());
    }

    public function test_a_malformed_counter_is_refused(): void
    {
        $device = $this->device();
        [$body, $headers] = $this->signedCrash($device, 1001);
        $headers[DeviceSignature::HEADER_COUNTER] = 'not-a-number';

        $this->sendSigned($body, $headers)->assertStatus(401);
    }

    public function test_re_provisioning_recovers_a_board_that_lost_its_flash(): void
    {
        $device = $this->device();

        // It has been reporting for a while, so the server's high-water mark
        // is well above where a freshly erased board would start counting.
        [$body, $headers] = $this->signedCrash($device, 5000000);
        $this->sendSigned($body, $headers)->assertCreated();

        $oldSecret = $device->fresh()->signing_secret;

        $officer = \App\Models\TocPersonnel::forceCreate([
            'full_name' => 'TOC Duty', 'email' => 'toc@example.test',
            'password' => \Illuminate\Support\Facades\Hash::make('secret123'),
        ]);
        $this->actingAs($officer, 'toc')
            ->post("/toc/devices/{$device->id}/reprovision")
            ->assertRedirect(route('toc.devices.index'));

        $device->refresh();
        $this->assertNotSame($oldSecret, $device->signing_secret, 'a fresh secret, so old traffic is worthless');
        $this->assertSame('0', (string) $device->last_signature_counter);
        $this->assertNotNull($device->signature_required_since, 'still must sign - losing a secret is not a downgrade');

        // Anything captured under the old secret is now refused, even though
        // its counter would clear the reset high-water mark.
        $stale = [$body, [
            DeviceSignature::HEADER_COUNTER   => '5000001',
            DeviceSignature::HEADER_SIGNATURE => DeviceSignature::compute(
                $oldSecret, $device->device_code, '5000001', $body,
            ),
            'Content-Type' => 'application/json',
        ]];
        $this->sendSigned($stale[0], $stale[1])->assertStatus(401);

        // The re-provisioned board starts from one again, and is accepted.
        [$freshBody, $freshHeaders] = $this->signedCrash($device->fresh(), 1000001);
        $this->sendSigned($freshBody, $freshHeaders)->assertCreated();

        $this->assertSame('1000001', (string) $device->fresh()->last_signature_counter);
    }

    public function test_the_signature_covers_the_query_on_a_get(): void
    {
        $device = $this->device();
        $query  = 'device_code=' . $device->device_code;
        $counter = '2001';

        $headers = [
            DeviceSignature::HEADER_COUNTER   => $counter,
            DeviceSignature::HEADER_SIGNATURE => DeviceSignature::compute(
                $device->signing_secret, $device->device_code, $counter, $query,
            ),
        ];

        // No emergency contact on file, so the endpoint's own 404 is the proof
        // it got past the signature check rather than being refused at 401.
        $this->call('GET', '/api/device/emergency-contact?' . $query, [], [], [],
            $this->transformHeadersToServerVars($headers))->assertStatus(404);
    }
}
