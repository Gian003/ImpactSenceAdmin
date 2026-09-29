<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\EmergencyContact;
use App\Models\Incident;
use App\Models\IncidentEvent;
use App\Models\IncidentFieldPhoto;
use App\Models\IncidentFieldReport;
use App\Models\IncidentRecord;
use App\Models\InvestigationOfficer;
use App\Models\PatrolUnit;
use App\Models\User;
use App\Services\FcmService;
use App\Services\GeocodingService;
use App\Services\VoiceCallService;
use App\Support\PatrolAlertSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * One crash, followed all the way through the system, asserting the data at
 * every hand-off:
 *
 *   device  -> incident record, timeline
 *           -> SMS to the rider's emergency contact (Semaphore)
 *           -> spoken call to the TOC hotline (Twilio)
 *           -> push to the rider's phone
 *           -> call and push to the nearest on-duty patrol unit
 *   patrol  -> accepts, arrives, files a field report with a photograph
 *           -> resolves, which pushes the rider
 *   invest. -> opens the incident report and saves an IRF record
 *   rider   -> the voice assistant's own report, and cancelling it
 *
 * The outside services are faked, so this proves our data and ordering, not
 * that Twilio, Semaphore or Firebase deliver. Those need a real drill — see
 * the readiness checklist.
 */
class CrashToInvestigationTest extends TestCase
{
    private const LAT = 15.9754;
    private const LNG = 120.5697;
    private const PLACE = 'Poblacion, Urdaneta City';

    private $voice;
    private $fcm;

    /** @var array<int, array{to: string, lines: array}> */
    private array $calls = [];

    /** @var array<int, array{token: string, title: string, body: string, data: array}> */
    private array $pushes = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.chain_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default'      => 'chain_test',
            'broadcasting.default'  => 'null',
            'queue.default'         => 'sync',
            'services.twilio.toc_number'   => '+639652145185',
            'services.twilio.account_sid'  => 'AC-test',
            'services.twilio.auth_token'   => 'token-test',
            'services.twilio.from_number'  => '+15550000000',
            'services.semaphore.api_key'   => 'semaphore-test',
            'services.semaphore.sender_name' => 'ImpactSense',
            'services.patrol_alert' => [
                'enabled' => true, 'radius_km' => 5, 'accept_seconds' => 90,
                'max_units' => 1, 'call' => true,
            ],
        ]);
        DB::purge('chain_test');
        PatrolAlertSchema::forget();
        $this->buildSchema();

        Storage::fake('local');
        Storage::fake('public');
        Http::fake(['api.semaphore.co/*' => Http::response(['status' => 'Queued'], 200)]);

        // Twilio and Firebase are recorded rather than called.
        $this->voice = Mockery::mock(VoiceCallService::class);
        $this->voice->allows('call')->andReturnUsing(function (string $to, array $lines) {
            $this->calls[] = ['to' => $to, 'lines' => $lines];
            return 'CA' . count($this->calls);
        });
        $this->app->instance(VoiceCallService::class, $this->voice);

        $this->fcm = Mockery::mock(FcmService::class);
        $this->fcm->allows('sendToToken')->andReturnUsing(function ($token, $title, $body, $data = []) {
            $this->pushes[] = compact('token', 'title', 'body', 'data');
            return true;
        });
        $this->fcm->allows('notifyRider')->andReturnUsing(function ($rider, $title, $body, $data = []) {
            if ($rider->fcm_token) {
                $this->pushes[] = ['token' => $rider->fcm_token, 'title' => $title, 'body' => $body, 'data' => $data];
            }
        });
        $this->fcm->allows('notifyPatrol')->andReturnUsing(function ($patrol, $title, $body, $data = []) {
            if ($patrol->fcm_token) {
                $this->pushes[] = ['token' => $patrol->fcm_token, 'title' => $title, 'body' => $body, 'data' => $data];
            }
        });
        $this->app->instance(FcmService::class, $this->fcm);

        $geo = Mockery::mock(GeocodingService::class);
        $geo->allows('reverse')->andReturn(self::PLACE);
        $this->app->instance(GeocodingService::class, $geo);
    }

    protected function tearDown(): void
    {
        PatrolAlertSchema::forget();
        parent::tearDown();
    }

    private function buildSchema(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('full_name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('phone_number')->nullable();
            $t->string('role')->default('rider');
            $t->string('fcm_token')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('devices', function (Blueprint $t) {
            $t->id();
            $t->string('device_code')->unique();
            $t->unsignedBigInteger('rider_id')->nullable();
            $t->string('pairing_key')->nullable();
            $t->string('model')->nullable();
            $t->string('sim_phone_number')->nullable();
            $t->boolean('is_active')->default(true);
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
        Schema::create('investigation_officers', function (Blueprint $t) {
            $t->id();
            $t->string('full_name');
            $t->string('badge_number')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('rank')->nullable();
            $t->string('unit_assignment')->nullable();
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
            $t->unsignedTinyInteger('vehicles_involved')->nullable();
            $t->unsignedTinyInteger('injured_count')->nullable();
            $t->string('road_condition', 40)->nullable();
            $t->string('weather_condition', 40)->nullable();
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
        Schema::create('incident_field_reports', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('incident_id');
            $t->unsignedBigInteger('patrol_unit_id')->nullable();
            $t->text('narrative')->nullable();
            $t->unsignedTinyInteger('vehicles_involved')->nullable();
            $t->unsignedTinyInteger('injured_count')->nullable();
            $t->string('road_condition', 40)->nullable();
            $t->string('weather_condition', 40)->nullable();
            $t->decimal('submitted_latitude', 10, 7)->nullable();
            $t->decimal('submitted_longitude', 10, 7)->nullable();
            $t->timestamp('submitted_at');
            $t->timestamps();
        });
        Schema::create('incident_field_photos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('incident_field_report_id');
            $t->string('path');
            $t->string('original_filename')->nullable();
            $t->string('mime_type', 100)->nullable();
            $t->unsignedInteger('size_bytes')->nullable();
            $t->string('sha256', 64)->nullable();
            $t->timestamp('captured_at')->nullable();
            $t->timestamps();
        });
        Schema::create('incident_records', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('incident_id')->nullable();
            $t->unsignedBigInteger('generated_by')->nullable();
            $t->json('data')->nullable();
            $t->timestamp('printed_at')->nullable();
            $t->timestamps();
        });
    }

    /** @return array{rider: User, device: Device, unit: PatrolUnit} */
    private function seedWorld(): array
    {
        // The stray "N/A" is how the rider's name is really stored when a
        // middle name is missing — it must not reach the SMS or the call.
        $rider = User::forceCreate([
            'full_name' => 'Juan Dela Cruz N/A', 'role' => 'rider',
            'phone_number' => '09170000001', 'fcm_token' => 'rider-token',
            'email' => 'rider@example.test', 'password' => Hash::make('secret123'),
        ]);
        EmergencyContact::forceCreate([
            'rider_id' => $rider->id, 'name' => 'Maria Dela Cruz',
            'phone_number' => '09170000002', 'relationship' => 'Spouse',
        ]);
        $device = Device::forceCreate([
            'device_code' => 'IMP-001', 'rider_id' => $rider->id, 'model' => 'ImpactSense Pro X1',
        ]);
        $unit = PatrolUnit::forceCreate([
            'full_name' => 'PO1 Reyes', 'badge_number' => 'PNP-4412',
            'mobile_number' => '0917-555-1234', 'fcm_token' => 'patrol-token',
            'current_latitude' => self::LAT + 0.005, 'current_longitude' => self::LNG,
            'last_seen_at' => now()->subSeconds(20), 'status' => 'available', 'on_duty' => true,
        ]);

        return compact('rider', 'device', 'unit');
    }

    private function callTo(string $number): ?array
    {
        foreach ($this->calls as $call) {
            if ($call['to'] === $number) {
                return $call;
            }
        }
        return null;
    }

    private function pushTo(string $token): ?array
    {
        foreach ($this->pushes as $push) {
            if ($push['token'] === $token) {
                return $push;
            }
        }
        return null;
    }

    public function test_the_whole_chain_from_crash_to_investigation_report(): void
    {
        ['rider' => $rider, 'device' => $device, 'unit' => $unit] = $this->seedWorld();

        // ── 1. the device reports a crash ────────────────────────────────────
        $this->postJson('/api/device/incident', [
            'device_code'       => 'IMP-001',
            'latitude'          => self::LAT,
            'longitude'         => self::LNG,
            'type'              => 'collision',
            'severity'          => 'high',
            'location_verified' => true,
        ])->assertCreated()->assertJsonPath('data.status', 'pending');

        $incident = Incident::sole();
        $this->assertSame($rider->id, $incident->rider_id, 'the crash is attached to the paired rider');
        $this->assertSame($device->id, $incident->device_id);
        $this->assertTrue($incident->location_verified);
        $this->assertSame('pending', $incident->status);
        $this->assertSame(self::PLACE, $incident->address, 'the place name is resolved and kept');

        // ── 2. SMS to the emergency contact ──────────────────────────────────
        $sms = null;
        Http::assertSent(function ($request) use (&$sms) {
            if (! str_contains($request->url(), 'semaphore.co')) {
                return false;
            }
            $sms = $request->data();
            return true;
        });
        $this->assertSame('09170000002', $sms['number'], 'texted the emergency contact, not the rider');
        $this->assertStringContainsString('Juan Dela Cruz', $sms['message']);
        $this->assertStringNotContainsString('N/A', $sms['message'], 'the missing name part never reaches the family');
        $this->assertStringContainsString(self::PLACE, $sms['message']);
        $this->assertStringContainsString('Severity: high', $sms['message']);
        $this->assertStringContainsString('https://maps.google.com/?q=15.9754', $sms['message']);

        // ── 3. spoken call to the TOC hotline ────────────────────────────────
        $toc = $this->callTo('+639652145185');
        $this->assertNotNull($toc, 'the TOC hotline was called');
        $spoken = implode(' ', $toc['lines']);
        $this->assertStringContainsString('Juan Dela Cruz', $spoken);
        $this->assertStringNotContainsString('N/A', $spoken);
        $this->assertStringContainsString(self::PLACE, $spoken);
        $this->assertStringNotContainsString('http', $spoken, 'a URL read aloud is unusable');
        $this->assertNotNull($incident->fresh()->twilio_call_sid, 'the call is tied back to the incident');

        // ── 4. push to the rider's phone ─────────────────────────────────────
        $riderPush = $this->pushTo('rider-token');
        $this->assertNotNull($riderPush);
        $this->assertSame('Crash Detected', $riderPush['title']);
        $this->assertSame((string) $incident->id, $riderPush['data']['incident_id']);

        // ── 5. the nearest patrol unit, at the same time ─────────────────────
        $patrolCall = $this->callTo('+639175551234');
        $this->assertNotNull($patrolCall, 'the nearest unit was called on its normalised number');
        $this->assertStringContainsString('nearest available unit', implode(' ', $patrolCall['lines']));
        $patrolPush = $this->pushTo('patrol-token');
        $this->assertNotNull($patrolPush);
        $this->assertSame('nearest_patrol_alert', $patrolPush['data']['type']);

        $alert = IncidentEvent::where('type', IncidentEvent::PATROL_ALERTED)->sole();
        $this->assertSame('PO1 Reyes', $alert->payload['patrol_unit']);
        $this->assertNull($incident->fresh()->patrol_unit_id, 'alerting is not assigning');

        // ── 6. the officer accepts, arrives ──────────────────────────────────
        Sanctum::actingAs($unit, ['*']);
        $this->patchJson("/api/patrol/incidents/{$incident->id}/status", ['status' => 'dispatched'])->assertOk();
        $this->patchJson("/api/patrol/incidents/{$incident->id}/status", ['status' => 'arrived'])->assertOk();

        $incident->refresh();
        $this->assertSame($unit->id, $incident->patrol_unit_id);
        $this->assertNotNull($incident->dispatched_at);
        $this->assertNotNull($incident->arrived_at);
        $this->assertSame('dispatched', $unit->fresh()->status);

        // ── 7. the field report from the scene ───────────────────────────────
        $this->postJson("/api/patrol/incidents/{$incident->id}/field-report", [
            'narrative'         => 'Rider conscious, minor abrasions. Motorcycle upright on the shoulder.',
            'vehicles_involved' => 2,
            'injured_count'     => 1,
            'road_condition'    => 'Wet',
            'weather_condition' => 'Rainy',
            'latitude'          => self::LAT,
            'longitude'         => self::LNG,
            'photos'            => [UploadedFile::fake()->create('scene.jpg', 120, 'image/jpeg')],
        ])->assertOk(); // 200, unlike the crash endpoint's 201 — the app relies on it

        $report = IncidentFieldReport::sole();
        $this->assertSame($unit->id, $report->patrol_unit_id);
        $this->assertSame(2, $report->vehicles_involved);
        $this->assertCount(1, $report->photos, 'the photograph is kept with the report');
        Storage::disk(IncidentFieldPhoto::DISK)->assertExists($report->photos->first()->path);

        // The responder's figures reach the investigator's working copy.
        $incident->refresh();
        $this->assertSame(2, $incident->vehicles_involved);
        $this->assertSame('Wet', $incident->road_condition);
        $this->assertSame('Rainy', $incident->weather_condition);

        // ── 8. resolved, and the rider is told ───────────────────────────────
        $this->patchJson("/api/patrol/incidents/{$incident->id}/status", ['status' => 'resolved'])->assertOk();
        $incident->refresh();
        $this->assertSame('resolved', $incident->status);
        $this->assertNotNull($incident->resolved_at);
        $this->assertSame('available', $unit->fresh()->status, 'still on shift, so back in the pool');

        $resolvedPush = collect($this->pushes)->firstWhere('title', 'Incident Resolved');
        $this->assertNotNull($resolvedPush, 'the rider is told help arrived');
        $this->assertSame('rider-token', $resolvedPush['token']);

        // ── 9. the timeline an investigator will read ────────────────────────
        $timeline = IncidentEvent::where('incident_id', $incident->id)
            ->orderBy('occurred_at')->orderBy('id')->pluck('type')->all();
        $this->assertSame([
            IncidentEvent::REPORTED,
            IncidentEvent::PATROL_ALERTED,
            IncidentEvent::DISPATCHED,
            IncidentEvent::ARRIVED,
            IncidentEvent::FIELD_REPORT_FILED,
            IncidentEvent::RESOLVED,
        ], $timeline);

        // ── 10. investigation opens the report ───────────────────────────────
        $officer = InvestigationOfficer::forceCreate([
            'full_name' => 'SPO2 Cruz', 'badge_number' => 'PNP-9001',
            'email' => 'invest@example.test', 'password' => Hash::make('secret123'), 'rank' => 'SPO2',
        ]);
        $this->actingAs($officer, 'investigation');

        $page = $this->get("/investigation/incident-report/{$incident->id}")->assertOk();
        $page->assertViewHas('fullName', 'Juan Dela Cruz N/A');
        $page->assertViewHas('reportedBy', 'PO1 Reyes');
        $page->assertViewHas('unit', 'PNP-4412');
        $page->assertViewHas('deviceCode', 'IMP-001');
        $page->assertViewHas('location', self::PLACE);
        $page->assertViewHas('status', 'resolved');
        $page->assertViewHas('vehicles', 2);
        $page->assertViewHas('weather', 'Rainy');
        $this->assertCount(1, $page->viewData('fieldReports'), 'the scene report reaches the investigator');
        $this->assertCount(4, $page->viewData('timeline'), 'reported, dispatched, arrived, resolved');

        // ── 11. investigation saves the IRF ──────────────────────────────────
        $this->post('/investigation/incident-records', [
            'incident_id'  => $incident->id,
            'case_number'  => 'UC-2026-0001',
            'narrative'    => 'Investigated and filed.',
            'printed'      => '1',
        ])->assertOk()->assertJsonPath('success', true);

        $record = IncidentRecord::sole();
        $this->assertSame($incident->id, $record->incident_id);
        $this->assertSame($officer->id, $record->generated_by);
        $this->assertSame('UC-2026-0001', $record->data['case_number']);
        $this->assertNotNull($record->printed_at);
        $this->assertArrayNotHasKey('_token', $record->data);
    }

    public function test_the_rider_voice_assistant_files_and_cancels_its_own_report(): void
    {
        ['rider' => $rider] = $this->seedWorld();
        Sanctum::actingAs($rider, ['*']);

        // The screen reads the contact back to the rider before reporting.
        $this->getJson('/api/rider/emergency-contacts')
            ->assertOk()
            ->assertJsonPath('data.0.phone_number', '09170000002');

        $this->postJson('/api/rider/incidents', [
            'type' => 'collision', 'latitude' => self::LAT, 'longitude' => self::LNG, 'severity' => 'high',
        ])->assertCreated();

        $incident = Incident::sole();
        $this->assertSame($rider->id, $incident->rider_id);
        $this->assertSame('mobile app', IncidentEvent::where('type', IncidentEvent::REPORTED)->sole()->payload['source']);

        // Same two outside channels as a device report.
        $this->assertNotNull($this->callTo('+639652145185'), 'the TOC hotline is called for an app report too');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'semaphore.co'));

        // Nobody is alerted: a phone report carries no confirmed GPS flag.
        $this->assertCount(0, IncidentEvent::where('type', IncidentEvent::PATROL_ALERTED)->get());
        $this->assertStringContainsString(
            'did not confirm',
            IncidentEvent::where('type', IncidentEvent::PATROL_ALERT_ENDED)->sole()->payload['reason'],
        );

        // "It was a false alarm."
        $this->patchJson("/api/rider/incidents/{$incident->id}/cancel")->assertOk();
        $this->assertSame('false_alarm', $incident->fresh()->status);
    }
}
