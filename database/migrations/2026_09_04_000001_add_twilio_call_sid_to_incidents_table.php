<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Twilio's Call SID for the TTS alert call placed to the TOC hotline
     * (see EmergencyNotificationService). Twilio's recordings carry the
     * call_sid they belong to, so storing it here is what lets the Call
     * Recordings page say which crash each recording was actually about -
     * without it, recordings are just a list of timestamps to correlate by
     * hand.
     *
     * Nullable because the call is best-effort: it's skipped entirely when
     * Twilio isn't configured, and a failed call still leaves a perfectly
     * valid incident record behind. Indexed because the recordings page
     * looks incidents up by this column, never the other way round.
     */
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('twilio_call_sid')->nullable()->index()->after('weather_condition');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['twilio_call_sid']);
            $table->dropColumn('twilio_call_sid');
        });
    }
};
