<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SimulateDevice extends Command
{
    protected $signature = 'simulate:device
        {--device=IMP-001 : device_code of the paired helmet}
        {--impact=HIGH : Impact level to simulate (LOW, MODERATE, HIGH, CRITICAL)}';

    protected $description = 'Simulate an ESP32 helmet device posting a crash incident to the backend';

    private const BASE_URL = 'http://192.168.1.4:8000';

    private const SEVERITY_MAP = [
        'LOW'      => 'low',
        'MODERATE' => 'medium',
        'HIGH'     => 'high',
        'CRITICAL' => 'critical',
    ];

    public function handle(): int
    {
        $deviceCode = $this->option('device');
        $impactLevel = strtoupper($this->option('impact'));

        if (! isset(self::SEVERITY_MAP[$impactLevel])) {
            $this->error("Unknown impact level '{$impactLevel}'. Use LOW, MODERATE, HIGH, or CRITICAL.");

            return self::FAILURE;
        }

        // Fake GPS near Urdaneta City, with small jitter so repeated runs aren't identical
        $latitude = round(15.9754 + $this->jitter(), 7);
        $longitude = round(120.5650 + $this->jitter(), 7);

        // Fake MPU6050-style readings. The backend doesn't accept/store these yet,
        // so they're only printed here to simulate what the real sensor would report.
        $accelValue = round(mt_rand(150, 400) / 10, 1);
        $gyroValue = round(mt_rand(500, 3000) / 10, 1);

        $this->info("Simulating {$impactLevel} impact crash from device [{$deviceCode}]");
        $this->line("  GPS:   {$latitude}, {$longitude}");
        $this->line("  Accel: {$accelValue} g | Gyro: {$gyroValue} deg/s  (sensor readout only, not sent to API)");
        $this->newLine();

        $response = Http::post(self::BASE_URL.'/api/device/incident', [
            'device_code' => $deviceCode,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'type'        => 'collision',
            'severity'    => self::SEVERITY_MAP[$impactLevel],
        ]);

        $this->line("HTTP Status: {$response->status()}");
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
        $this->newLine();

        if ($response->successful()) {
            $this->info('Crash incident reported successfully.');

            return self::SUCCESS;
        }

        $this->error('Backend rejected the request.');

        if ($response->status() === 404) {
            $this->warn("No helmet with device_code '{$deviceCode}' is registered. Create one first — see setup instructions.");
        } elseif ($response->status() === 422) {
            $this->warn("Helmet '{$deviceCode}' exists but has no paired rider yet.");
        }

        return self::FAILURE;
    }

    private function jitter(): float
    {
        // ~ +/- 0.005 degrees, roughly +/- 500 meters
        return mt_rand(-50, 50) / 10000;
    }
}
