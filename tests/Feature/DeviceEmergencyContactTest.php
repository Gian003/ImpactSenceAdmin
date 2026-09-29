<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\EmergencyContact;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The device caches this response in flash and reads the rider's name back
 * out of it into the SMS it sends from a crash scene, so a name with a stray
 * "N/A" in it ends up in a message a family member receives.
 */
class DeviceEmergencyContactTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.emergency_contact_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'emergency_contact_test',
        ]);
        DB::purge('emergency_contact_test');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('full_name')->nullable();
            $t->timestamps();
        });
        Schema::create('devices', function (Blueprint $t) {
            $t->id();
            $t->string('device_code')->unique();
            $t->unsignedBigInteger('rider_id')->nullable();
            $t->string('pairing_key')->nullable();
            $t->string('sim_phone_number')->nullable();
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
    }

    private function fetchFor(string $fullName): array
    {
        $rider = User::forceCreate(['full_name' => $fullName]);
        EmergencyContact::forceCreate([
            'rider_id' => $rider->id, 'name' => 'Maria Dela Cruz', 'phone_number' => '09170000000',
        ]);
        Device::forceCreate(['device_code' => 'IMP-' . $rider->id, 'rider_id' => $rider->id]);

        return $this->getJson('/api/device/emergency-contact?device_code=IMP-' . $rider->id)
            ->assertOk()
            ->json('data');
    }

    public function test_a_missing_name_part_is_not_sent_to_the_device(): void
    {
        $this->assertSame('Juan Dela Cruz', $this->fetchFor('Juan Dela Cruz N/A')['rider_name']);
    }

    public function test_a_complete_name_is_unchanged(): void
    {
        $this->assertSame('Juan Santos Dela Cruz', $this->fetchFor('Juan Santos Dela Cruz')['rider_name']);
    }

    public function test_a_name_that_cleans_away_entirely_falls_back(): void
    {
        // Better than an SMS that opens with a blank where a name should be.
        $this->assertSame('A registered ImpactSense rider', $this->fetchFor('N/A')['rider_name']);
    }
}
