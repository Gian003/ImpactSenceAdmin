<?php

namespace Database\Seeders;

use App\Models\Helmet;
use Illuminate\Database\Seeder;

class TestDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            // Physical ESP32 device (both firmware versions use this code)
            ['device_code' => 'IMP-001',          'pairing_key' => 'IMPKEY01', 'model' => 'ImpactSense Pro X1',    'firmware_version' => '2.2.0'],
            // Extra test slots for mobile pairing tests
            ['device_code' => 'ITK-TEST-DEV-001', 'pairing_key' => 'TESTKEY1', 'model' => 'ImpactSense Pro X1',    'firmware_version' => '2.0.1'],
            ['device_code' => 'ITK-TEST-DEV-002', 'pairing_key' => 'TESTKEY2', 'model' => 'ImpactSense Shield V2', 'firmware_version' => '2.1.0'],
            ['device_code' => 'ITK-TEST-DEV-003', 'pairing_key' => 'TESTKEY3', 'model' => 'ImpactSense Elite S3',  'firmware_version' => '2.2.0'],
        ];

        foreach ($devices as $d) {
            $helmet = Helmet::updateOrCreate(
                ['device_code' => $d['device_code']],
                [
                    'model'            => $d['model'],
                    'firmware_version' => $d['firmware_version'],
                    'battery_level'    => 100,
                    'is_active'        => false,
                    'rider_id'         => null,
                    'paired_at'        => null,
                ]
            );

            // Force the known pairing key (bypassing fillable guard)
            $helmet->pairing_key = $d['pairing_key'];
            $helmet->saveQuietly();
        }

        $this->command->info('3 test devices seeded (pairing keys: TESTKEY1, TESTKEY2, TESTKEY3).');
    }
}
