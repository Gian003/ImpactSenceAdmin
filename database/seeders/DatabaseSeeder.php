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
        // updateOrCreate (keyed by email, the actual login identifier) rather
        // than create() — re-running this seeder against a non-empty database
        // previously crashed with a duplicate-key violation on badge_number
        // instead of just leaving the existing account alone.

        // ── TOC Personnel (web dashboard → auth:toc guard → toc_personnel table) ──
        TocPersonnel::updateOrCreate(
            ['email' => 'dev.rodriguez2111@gmail.com'],
            [
                'full_name'       => 'TOC Admin',
                'badge_number'    => 'TOC-001',
                'password'        => Hash::make('password'),
                'rank'            => 'Inspector',
                'unit_assignment' => 'Urdaneta City Police Station',
                'role'            => 'supervisor',
            ]
        );

        // ── Investigation Officers (web dashboard → auth:investigation guard) ────
        InvestigationOfficer::updateOrCreate(
            ['email' => 'gianrodriguez003@gmail.com'],
            [
                'full_name'       => 'Investigation Admin',
                'badge_number'    => 'INV-001',
                'password'        => Hash::make('password'),
                'rank'            => 'Senior Inspector',
                'unit_assignment' => 'Urdaneta City Police Station',
            ]
        );

        // ── Rider (mobile app → auth:sanctum → users table) ─────────────────────
        User::updateOrCreate(
            ['email' => 'rider@impactsense.ph'],
            [
                'full_name' => 'Test Rider',
                'role'      => 'rider',
                'password'  => Hash::make('password'),
            ]
        );
    }
}
