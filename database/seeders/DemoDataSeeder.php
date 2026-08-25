<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Incident;
use App\Models\PatrolRegistration;
use App\Models\PatrolUnit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Patrol Units ──────────────────────────────────────────────────────
        $patrolData = [
            ['full_name' => 'SPO1 Andres Lumabao',   'badge_number' => 'PNP-U-001', 'email' => 'lumabao@patrol.demo',  'rank' => 'SPO1', 'mobile_number' => '09181000001', 'status' => 'available'],
            ['full_name' => 'PO2 Dennis Perez',       'badge_number' => 'PNP-U-002', 'email' => 'perez@patrol.demo',    'rank' => 'PO2',  'mobile_number' => '09181000002', 'status' => 'available'],
            ['full_name' => 'PO1 Maricel Tabor',      'badge_number' => 'PNP-U-003', 'email' => 'tabor@patrol.demo',    'rank' => 'PO1',  'mobile_number' => '09181000003', 'status' => 'off_duty'],
            ['full_name' => 'SPO2 Roberto Ibanez',    'badge_number' => 'PNP-U-004', 'email' => 'ibanez@patrol.demo',   'rank' => 'SPO2', 'mobile_number' => '09181000004', 'status' => 'available'],
            ['full_name' => 'PO3 Christine Esguerra', 'badge_number' => 'PNP-U-005', 'email' => 'esguerra@patrol.demo', 'rank' => 'PO3',  'mobile_number' => '09181000005', 'status' => 'dispatched'],
        ];

        $patrolIds = [];
        foreach ($patrolData as $p) {
            $unit = PatrolUnit::updateOrCreate(
                ['email' => $p['email']],
                array_merge($p, ['password' => Hash::make('password')])
            );
            $patrolIds[] = $unit->id;
        }

        // ── Riders ────────────────────────────────────────────────────────────
        $ridersData = [
            ['full_name' => 'Juan dela Cruz',        'email' => 'juan.delacruz@demo.ph',     'phone_number' => '09171230001', 'address' => 'Nancamaliran West, Urdaneta City',  'date_of_birth' => '1990-03-15'],
            ['full_name' => 'Maria Santos',          'email' => 'maria.santos@demo.ph',      'phone_number' => '09171230002', 'address' => 'Cabuloan, Urdaneta City',           'date_of_birth' => '1995-07-22'],
            ['full_name' => 'Pedro Reyes',           'email' => 'pedro.reyes@demo.ph',       'phone_number' => '09171230003', 'address' => 'San Vicente, Urdaneta City',        'date_of_birth' => '1988-11-08'],
            ['full_name' => 'Ana Gonzalez',          'email' => 'ana.gonzalez@demo.ph',      'phone_number' => '09171230004', 'address' => 'Pinmaludpod, Urdaneta City',        'date_of_birth' => '1992-04-30'],
            ['full_name' => 'Jose Mendoza',          'email' => 'jose.mendoza@demo.ph',      'phone_number' => '09171230005', 'address' => 'Bolaoen, Urdaneta City',            'date_of_birth' => '1985-09-12'],
            ['full_name' => 'Rosa Garcia',           'email' => 'rosa.garcia@demo.ph',       'phone_number' => '09171230006', 'address' => 'Consolacion, Urdaneta City',        'date_of_birth' => '1998-01-25'],
            ['full_name' => 'Miguel Torres',         'email' => 'miguel.torres@demo.ph',     'phone_number' => '09171230007', 'address' => 'Macalong, Urdaneta City',           'date_of_birth' => '1993-06-17'],
            ['full_name' => 'Carmen Flores',         'email' => 'carmen.flores@demo.ph',     'phone_number' => '09171230008', 'address' => 'Cayambanan, Urdaneta City',         'date_of_birth' => '1991-12-03'],
            ['full_name' => 'Roberto Villanueva',    'email' => 'roberto.villanueva@demo.ph','phone_number' => '09171230009', 'address' => 'Nancamaliran East, Urdaneta City',  'date_of_birth' => '1987-08-19'],
            ['full_name' => 'Elena Pascual',         'email' => 'elena.pascual@demo.ph',     'phone_number' => '09171230010', 'address' => 'Anonas, Urdaneta City',             'date_of_birth' => '1996-02-14'],
            ['full_name' => 'Antonio Ramos',         'email' => 'antonio.ramos@demo.ph',     'phone_number' => '09171230011', 'address' => 'Bactad East, Urdaneta City',        'date_of_birth' => '1983-10-05'],
            ['full_name' => 'Isabella Cruz',         'email' => 'isabella.cruz@demo.ph',     'phone_number' => '09171230012', 'address' => 'Cabaruan, Urdaneta City',           'date_of_birth' => '2000-05-28'],
            ['full_name' => 'Francisco Bautista',    'email' => 'francisco.bautista@demo.ph','phone_number' => '09171230013', 'address' => 'Dilan-Paurido, Urdaneta City',      'date_of_birth' => '1989-07-11'],
            ['full_name' => 'Luisa Aquino',          'email' => 'luisa.aquino@demo.ph',      'phone_number' => '09171230014', 'address' => 'Poblacion, Urdaneta City',          'date_of_birth' => '1994-03-07'],
            ['full_name' => 'Manuel Ocampo',         'email' => 'manuel.ocampo@demo.ph',     'phone_number' => '09171230015', 'address' => 'Nancayasan, Urdaneta City',         'date_of_birth' => '1986-09-23'],
        ];

        $riderIds    = [];
        $deviceIds   = [];
        $deviceModels = ['ImpactSense Pro X1', 'ImpactSense Shield V2', 'ImpactSense Elite S3', 'ImpactSense Guard M1'];

        foreach ($ridersData as $i => $r) {
            $rider = User::updateOrCreate(
                ['email' => $r['email']],
                array_merge($r, ['role' => 'rider', 'password' => Hash::make('password')])
            );
            $riderIds[] = $rider->id;

            // Emergency contacts
            if (DB::table('emergency_contacts')->where('rider_id', $rider->id)->doesntExist()) {
                DB::table('emergency_contacts')->insert([
                    ['rider_id' => $rider->id, 'name' => 'Emergency Contact 1', 'phone_number' => '09170000' . str_pad($i * 2 + 1, 3, '0', STR_PAD_LEFT),     'relationship' => 'Spouse',  'created_at' => now(), 'updated_at' => now()],
                    ['rider_id' => $rider->id, 'name' => 'Emergency Contact 2', 'phone_number' => '09170000' . str_pad($i * 2 + 2, 3, '0', STR_PAD_LEFT),     'relationship' => 'Parent',  'created_at' => now(), 'updated_at' => now()],
                ]);
            }

            // Device
            $deviceCode = 'ITK-' . strtoupper(substr(md5($rider->email), 0, 4)) . '-GRP' . ($i + 1) . '-MDL1';
            $device = Device::updateOrCreate(
                ['device_code' => $deviceCode],
                [
                    'rider_id'         => $rider->id,
                    'model'            => $deviceModels[$i % count($deviceModels)],
                    'firmware_version' => '2.' . ($i % 5) . '.1',
                    'battery_level'    => rand(55, 98),
                    'is_active'        => true,
                    'paired_at'        => now()->subDays(rand(30, 365)),
                ]
            );
            $deviceIds[$rider->id] = $device->id;
        }

        // ── Incidents ────────────────────────────────────────────────────────
        // Skip if demo incidents already exist
        if (Incident::where('address', 'like', '%, Urdaneta City')->count() >= 10) {
            $this->command->info('Demo incidents already exist — skipping incident generation.');
        } else {
            $this->generateIncidents($riderIds, $deviceIds, $patrolIds);
        }

        // ── Pending Patrol Registrations ──────────────────────────────────────
        $pendingRegistrations = [
            ['first_name' => 'Carlo',   'last_name' => 'Macaraeg',   'email' => 'carlo.macaraeg@pending.demo',   'phone_number' => '09182000001'],
            ['first_name' => 'Janine',  'last_name' => 'Quilantang', 'email' => 'janine.quilantang@pending.demo', 'phone_number' => '09182000002'],
            ['first_name' => 'Bernard', 'last_name' => 'Domingo',    'email' => 'bernard.domingo@pending.demo',   'phone_number' => '09182000003'],
        ];

        foreach ($pendingRegistrations as $reg) {
            PatrolRegistration::updateOrCreate(
                ['email' => $reg['email']],
                array_merge($reg, ['password' => Hash::make('password'), 'status' => 'pending'])
            );
        }

        $this->command->info('Demo data seeded successfully.');
    }

    private function generateIncidents(array $riderIds, array $deviceIds, array $patrolIds): void
    {
        // Real Urdaneta City barangay locations with coordinates
        // Format: [address, lat, lng, weight (higher = more incidents there)]
        $locations = [
            ['National Highway, Nancamaliran West, Urdaneta City', 15.9766, 120.5719, 20],
            ['San Vicente St., Urdaneta City',                     15.9802, 120.5748, 10],
            ['Poblacion, Urdaneta City',                           15.9780, 120.5730,  8],
            ['Cabuloan Junction, Urdaneta City',                   15.9852, 120.5683,  7],
            ['Pinmaludpod, Urdaneta City',                         15.9721, 120.5681,  6],
            ['Nancamaliran East, Urdaneta City',                   15.9761, 120.5741,  5],
            ['Consolacion, Urdaneta City',                         15.9831, 120.5812,  4],
            ['Bolaoen, Urdaneta City',                             15.9698, 120.5652,  4],
            ['Bactad East, Urdaneta City',                         15.9811, 120.5682,  3],
            ['Macalong, Urdaneta City',                            15.9678, 120.5768,  3],
            ['Anonas Rd., Urdaneta City',                          15.9733, 120.5762,  2],
            ['Cayambanan, Urdaneta City',                          15.9901, 120.5723,  2],
            ['Cabaruan, Urdaneta City',                            15.9691, 120.5732,  2],
            ['Dilan-Paurido, Urdaneta City',                       15.9851, 120.5752,  2],
            ['Nancayasan, Urdaneta City',                          15.9855, 120.5795,  2],
        ];

        // Build a weighted location pool
        $locationPool = [];
        foreach ($locations as $loc) {
            for ($w = 0; $w < $loc[3]; $w++) {
                $locationPool[] = $loc;
            }
        }

        // Peak hours (biased toward rush hours and night)
        $hourPool    = [6, 7, 7, 7, 8, 8, 9, 11, 12, 12, 12, 13, 14, 16, 17, 17, 17, 18, 18, 19, 20, 20, 21, 22, 23, 0, 1];
        $severities  = ['critical', 'critical', 'high', 'high', 'high', 'high', 'medium', 'medium', 'medium', 'medium', 'low', 'low'];
        $types       = ['collision', 'collision', 'collision', 'collision', 'fall', 'fall', 'other'];
        $notes       = [
            'Rider lost control on a curve.',
            'Vehicle swerved to avoid a pothole.',
            'Side-swiped by a passenger jeepney.',
            'Collision at uncontrolled intersection.',
            'Rider skidded on wet road surface.',
            'Impact with road barrier.',
            'Collision with tricycle.',
            'Rider fell due to tire blowout.',
            'Rear-ended by motorcycle.',
            'Rider lost control at speed bump.',
            null, null,
        ];

        // Incidents per month for the last 12 months (index 0 = 12 months ago)
        $monthCounts = [3, 4, 4, 5, 6, 6, 7, 7, 8, 8, 9, 8];

        $rows = [];
        $now  = Carbon::now();

        foreach ($monthCounts as $monthsAgo => $count) {
            $baseDate = $now->copy()->subMonths(11 - $monthsAgo)->startOfMonth();

            for ($n = 0; $n < $count; $n++) {
                $loc      = $locationPool[array_rand($locationPool)];
                $riderId  = $riderIds[array_rand($riderIds)];
                $severity = $severities[array_rand($severities)];
                $type     = $types[array_rand($types)];
                $hour     = $hourPool[array_rand($hourPool)];
                $note     = $notes[array_rand($notes)];

                // Pick a random day within the month
                $daysInMonth = $baseDate->copy()->daysInMonth;
                $day         = rand(1, min($daysInMonth, ($monthsAgo === 11 ? $now->day : $daysInMonth)));
                $createdAt   = $baseDate->copy()->setDay($day)->setHour($hour)->setMinute(rand(0, 59));

                // Determine status based on how old the incident is
                $ageInDays = $now->diffInDays($createdAt);
                if ($ageInDays > 14) {
                    $statusPool = ['resolved', 'resolved', 'resolved', 'false_alarm'];
                } elseif ($ageInDays > 3) {
                    $statusPool = ['resolved', 'resolved', 'dispatched', 'false_alarm'];
                } else {
                    $statusPool = ['pending', 'pending', 'dispatched', 'resolved'];
                }
                $status = $statusPool[array_rand($statusPool)];

                $dispatchedAt = null;
                $resolvedAt   = null;
                $patrolId     = null;

                if (in_array($status, ['dispatched', 'resolved'])) {
                    $dispatchedAt = $createdAt->copy()->addMinutes(rand(5, 20));
                    $patrolId     = $patrolIds[array_rand($patrolIds)];
                }
                if ($status === 'resolved') {
                    $resolvedAt = $dispatchedAt->copy()->addMinutes(rand(15, 60));
                }

                $rows[] = [
                    'rider_id'       => $riderId,
                    'device_id'      => $deviceIds[$riderId] ?? null,
                    'patrol_unit_id' => $patrolId,
                    'type'           => $type,
                    'latitude'       => $loc[1] + (rand(-30, 30) / 10000),
                    'longitude'      => $loc[2] + (rand(-30, 30) / 10000),
                    'address'        => $loc[0],
                    'severity'       => $severity,
                    'status'         => $status,
                    'notes'          => $note,
                    'dispatched_at'  => $dispatchedAt?->toDateTimeString(),
                    'resolved_at'    => $resolvedAt?->toDateTimeString(),
                    'created_at'     => $createdAt->toDateTimeString(),
                    'updated_at'     => ($resolvedAt ?? $dispatchedAt ?? $createdAt)->toDateTimeString(),
                ];
            }
        }

        // Bulk insert bypassing Eloquent so we control created_at
        foreach (array_chunk($rows, 20) as $chunk) {
            DB::table('incidents')->insert($chunk);
        }

        $this->command->info(count($rows) . ' demo incidents inserted.');
    }
}
