<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds "arrived" between dispatched and resolved.
     *
     * The patrol app had no way to say "I'm on scene": its Mark as Arrived
     * button sent 'resolved', which closed the case and put the responder
     * off duty the moment they pulled up — before any work had been done.
     * Arrival is a milestone, not an outcome, so it needs its own state.
     *
     * Enum values can't be altered through the schema builder, so this is
     * raw DDL. Ordered deliberately: an incident moves left to right through
     * pending → dispatched → arrived → resolved, with false_alarm as the
     * separate off-ramp.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE incidents MODIFY COLUMN status
             ENUM('pending', 'dispatched', 'arrived', 'resolved', 'false_alarm')
             NOT NULL DEFAULT 'pending'"
        );

        Schema::table('incidents', function (Blueprint $table) {
            $table->timestamp('arrived_at')->nullable()->after('dispatched_at');
        });
    }

    public function down(): void
    {
        // Anything already sitting at 'arrived' has to be moved somewhere the
        // narrowed enum can still hold, or MySQL silently coerces it to ''.
        DB::table('incidents')->where('status', 'arrived')->update(['status' => 'dispatched']);

        DB::statement(
            "ALTER TABLE incidents MODIFY COLUMN status
             ENUM('pending', 'dispatched', 'resolved', 'false_alarm')
             NOT NULL DEFAULT 'pending'"
        );

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('arrived_at');
        });
    }
};
