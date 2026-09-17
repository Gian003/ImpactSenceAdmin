<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two facts the nearest-patrol alert cannot work without.
 *
 * patrol_units.on_duty — whether the officer has said they are taking calls.
 * `status` could not answer that: nothing ever set it to "available", and
 * finishing a call put a unit back to "off_duty", so an officer on shift and
 * free looked exactly like one who had gone home with the app still open.
 * Defaults to false, so nobody is alerted until they switch it on.
 *
 * incidents.location_verified — whether the coordinates came from a real
 * satellite fix. A device with no fix reports its configured fallback point,
 * central Urdaneta, and "nearest" measured from there sends the wrong unit.
 * Nullable: incidents from before this column, and from firmware that does
 * not send it, are genuinely unknown rather than unverified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrol_units', function (Blueprint $table) {
            $table->boolean('on_duty')->default(false)->after('status');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->boolean('location_verified')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('location_verified');
        });

        Schema::table('patrol_units', function (Blueprint $table) {
            $table->dropColumn('on_duty');
        });
    }
};
