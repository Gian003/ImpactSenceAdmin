<?php

namespace Database\Seeders;

use App\Models\InvestigationOfficer;
use App\Models\TocPersonnel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── TOC Personnel (web dashboard → auth:toc guard → toc_personnel table) ──
        TocPersonnel::create([
            'full_name'       => 'TOC Admin',
            'badge_number'    => 'TOC-001',
            'email'           => 'toc@impactsense.ph',
            'password'        => Hash::make('password'),
            'rank'            => 'Inspector',
            'unit_assignment' => 'Urdaneta City Police Station',
            'role'            => 'supervisor',
        ]);

        // ── Investigation Officers (web dashboard → auth:investigation guard) ────
        InvestigationOfficer::create([
            'full_name'       => 'Investigation Admin',
            'badge_number'    => 'INV-001',
            'email'           => 'investigation@impactsense.ph',
            'password'        => Hash::make('password'),
            'rank'            => 'Senior Inspector',
            'unit_assignment' => 'Urdaneta City Police Station',
        ]);

        // ── Rider (mobile app → auth:sanctum → users table) ─────────────────────
        User::factory()->create([
            'full_name' => 'Test Rider',
            'email'     => 'rider@impactsense.ph',
            'role'      => 'rider',
        ]);
    }
}
