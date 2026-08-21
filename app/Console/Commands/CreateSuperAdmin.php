<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature   = 'impactsense:create-superadmin';
    protected $description = 'Create a new ImpactSense superadmin account';

    public function handle(): int
    {
        $this->info('');
        $this->info('  ImpactSense — Create Superadmin');
        $this->info('  --------------------------------');

        $fullName = $this->ask('Full name');
        $badge    = $this->ask('Badge number');
        $rank     = $this->ask('Rank (e.g. Police Lieutenant Colonel)');
        $email    = $this->ask('Email address');
        $password = $this->secret('Password (min 8 characters)');
        $confirm  = $this->secret('Confirm password');

        $validator = Validator::make(compact('email', 'password'), [
            'email'    => ['required', 'email', 'unique:admins,email'],
            'password' => ['required', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error("  {$error}");
            }
            return self::FAILURE;
        }

        if ($password !== $confirm) {
            $this->error('  Passwords do not match.');
            return self::FAILURE;
        }

        $admin = Admin::create([
            'full_name'    => $fullName,
            'badge_number' => $badge,
            'email'        => $email,
            'password'     => Hash::make($password),
            'rank'         => $rank,
            'is_active'    => true,
        ]);

        $this->info('');
        $this->info("  ✓ Superadmin created: {$admin->full_name} <{$admin->email}>");
        $this->info("  Login at: " . url('/admin/login'));
        $this->info('');

        return self::SUCCESS;
    }
}
