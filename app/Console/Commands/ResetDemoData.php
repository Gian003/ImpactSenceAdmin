<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\InvestigationOfficer;
use App\Models\TocPersonnel;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Clears the operational data and re-seeds it, without touching the accounts
 * people sign in with.
 *
 * This exists because `migrate:fresh --seed` drops every table, and nothing
 * seeds the Command Center admin — so the usual "just reset it" would lock the
 * team out of /admin entirely and throw away the TOC and investigation logins
 * along with the demo data.
 */
class ResetDemoData extends Command
{
    protected $signature = 'app:reset-demo-data
                            {--force : Skip the confirmation prompt}
                            {--dry-run : Show what would be removed and kept, and change nothing}
                            {--keep-photos : Leave uploaded registration and roster photos on disk}';

    protected $description = 'Wipe riders, devices, incidents and patrol data, then re-seed — keeping admin, TOC and investigation logins';

    /**
     * Deleted in this order: children before parents, so foreign keys hold at
     * every step and a failure part-way cannot leave an incident pointing at a
     * rider who no longer exists.
     */
    private const WIPE = [
        'incident_field_photos',
        'incident_field_reports',
        'incident_events',
        'incident_records',
        'incidents',
        'emergency_contacts',
        'devices',
        'patrol_registrations',
        'personnel_roster',
        'patrol_units',
        'speed_reports',
        'speed_zones',
    ];

    /**
     * Tables and the column naming an uploaded file, so the command can delete
     * exactly the photos belonging to the rows it removes.
     *
     * It used to empty whole directories instead. storage/ is shared by every
     * database on the machine, so running this against a scratch database
     * still deleted the live system's photos — the rows were safe and the
     * files were not. Deleting only what the removed rows referenced makes
     * that impossible.
     */
    private const PHOTO_COLUMNS = [
        'patrol_registrations'  => 'photo_path',
        'personnel_roster'      => 'reference_photo_path',
        'incident_field_photos' => 'path',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $riders = DB::table('users')->where('role', 'rider')->count();

        $this->newLine();
        $this->line('<fg=yellow>Will be removed</>');
        $rows = [];
        foreach (self::WIPE as $table) {
            $rows[] = [$table, DB::table($table)->count()];
        }
        $rows[] = ['users (role = rider)', $riders];
        $this->table(['Table', 'Rows'], $rows);

        $this->line('<fg=green>Will be kept</>');
        $this->table(['Accounts', 'Rows'], [
            ['admins (Command Center)', Admin::count()],
            ['toc_personnel',           TocPersonnel::withTrashed()->count()],
            ['investigation_officers',  InvestigationOfficer::withTrashed()->count()],
            ['admin_invitations',       DB::table('admin_invitations')->count()],
            ['users (role != rider)',   DB::table('users')->where('role', '!=', 'rider')->count()],
        ]);

        if ($dryRun) {
            $this->info('Dry run — nothing was changed.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Remove the data above and re-seed? This cannot be undone.')) {
            $this->warn('Cancelled. Nothing was changed.');
            return self::SUCCESS;
        }

        // Gathered before the rows are deleted — afterwards there is nothing
        // left to say which files belonged to them.
        $photoPaths = [];
        if (! $this->option('keep-photos')) {
            foreach (self::PHOTO_COLUMNS as $table => $column) {
                $photoPaths = array_merge($photoPaths, DB::table($table)
                    ->whereNotNull($column)
                    ->pluck($column)
                    ->all());
            }
        }

        // One transaction: a failure half-way leaves the database as it was,
        // rather than half-wiped with a demo hours away.
        DB::transaction(function () {
            // MySQL will not let a parent row go while a child still points at
            // it, and the order above is only correct for the tables listed.
            // Disabling the checks inside the transaction covers anything a
            // later migration adds that this list has not caught up with.
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach (self::WIPE as $table) {
                    $deleted = DB::table($table)->delete();
                    $this->line(sprintf('  %-26s %d removed', $table, $deleted));
                }

                $riders = DB::table('users')->where('role', 'rider')->delete();
                $this->line(sprintf('  %-26s %d removed', 'users (riders)', $riders));
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });

        if ($photoPaths !== []) {
            Storage::disk('public')->delete($photoPaths);
            $this->line(sprintf('  %-26s %d photo(s) deleted',
                'uploaded photos', count($photoPaths)));
        }

        $this->newLine();
        $this->info('Re-seeding demo data…');
        $this->call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);

        $this->newLine();
        $this->info('Done. Staff logins were left untouched:');
        $this->line(sprintf('  %d admin, %d TOC, %d investigation',
            Admin::count(), TocPersonnel::withTrashed()->count(), InvestigationOfficer::withTrashed()->count()));

        if (Admin::count() === 0) {
            $this->warn('There is no Command Center admin — run: php artisan impactsense:create-superadmin');
        }

        return self::SUCCESS;
    }
}
