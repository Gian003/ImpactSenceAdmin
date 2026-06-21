<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'full_name' => 'TOC Admin',
            'email' => 'toc@impactsense.ph',
            'role' => 'toc',
        ]);

        User::factory()->create([
            'full_name' => 'Investigation Admin',
            'email' => 'investigation@impactsense.ph',
            'role' => 'investigation',
        ]);
    }
}
