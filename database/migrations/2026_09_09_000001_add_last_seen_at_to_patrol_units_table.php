<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When this patrol unit's app last checked in.
     *
     * The existing `status` column is a duty state (dispatched vs not), not a
     * presence one - which left TOC unable to tell an officer who is on shift
     * and free from one whose phone is switched off, since both sit at
     * off_duty. This column is what separates the two.
     *
     * Deliberately a heartbeat rather than a login flag: the patrol app
     * already pushes its position every 30 seconds while open, so stamping
     * that gives presence for free and, more importantly, it expires on its
     * own. A login flag would never clear - mobile apps are closed and killed
     * far more often than they are logged out of - so every patroller would
     * read "online" permanently within a week.
     */
    public function up(): void
    {
        Schema::table('patrol_units', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('current_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('patrol_units', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
    }
};
