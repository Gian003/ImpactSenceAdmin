<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets speed samples arrive from a rider's phone rather than only from a
     * helmet, and makes the zone aggregation survive real volume.
     *
     * Two changes:
     *
     * 1. device_id becomes nullable. The table was built assuming the ESP32
     *    would report speed; it never has, and no firmware path exists for it.
     *    The phone already runs a position stream during navigation and gets
     *    speed from the same fix for free, so that is where samples will
     *    actually come from.
     *
     *    Phone samples are deliberately stored with NO identity attached —
     *    not device_id, not rider_id. A zone's average speed does not require
     *    knowing who was driving, and the alternative is a continuous GPS
     *    movement log of private citizens sitting in a police database. The
     *    column stays for the firmware path, should it ever exist.
     *
     * 2. An index on created_at. Every zone figure is now computed over a
     *    trailing window, so that column is in the WHERE clause of every
     *    aggregate query; without an index those become full scans as soon as
     *    the table has real data in it.
     */
    /**
     * The foreign key still carries its original name,
     * speed_reports_helmet_id_foreign — the column was renamed device_id at
     * some point but the constraint was not. Looking it up beats guessing.
     */
    private function deviceForeignKeyName(): ?string
    {
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL',
            ['speed_reports', 'device_id']
        );

        return $row->CONSTRAINT_NAME ?? null;
    }

    public function up(): void
    {
        // Drop the FK before altering the column MySQL is using it for.
        if ($fk = $this->deviceForeignKeyName()) {
            DB::statement("ALTER TABLE speed_reports DROP FOREIGN KEY `{$fk}`");
        }

        DB::statement('ALTER TABLE speed_reports MODIFY COLUMN device_id BIGINT UNSIGNED NULL');

        Schema::table('speed_reports', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
            $table->index('created_at', 'speed_reports_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('speed_reports', function (Blueprint $table) {
            $table->dropIndex('speed_reports_created_at_index');
        });

        if ($fk = $this->deviceForeignKeyName()) {
            DB::statement("ALTER TABLE speed_reports DROP FOREIGN KEY `{$fk}`");
        }

        // Anything anonymous has to go before the column can be NOT NULL again.
        DB::table('speed_reports')->whereNull('device_id')->delete();

        DB::statement('ALTER TABLE speed_reports MODIFY COLUMN device_id BIGINT UNSIGNED NOT NULL');

        Schema::table('speed_reports', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
        });
    }
};
