<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These previously had no backing columns at all — the incident report
     * page showed hardcoded "1 vehicle, 1 injured, N/A road/weather" for
     * every incident regardless of what actually happened, since there was
     * nowhere to store the real values. Nullable because an IoT crash
     * detection report can't know any of this at the moment it's created —
     * it's filled in later by investigation staff once the real details are
     * known.
     */
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedTinyInteger('vehicles_involved')->nullable()->after('notes');
            $table->unsignedTinyInteger('injured_count')->nullable()->after('vehicles_involved');
            $table->string('road_condition')->nullable()->after('injured_count');
            $table->string('weather_condition')->nullable()->after('road_condition');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['vehicles_involved', 'injured_count', 'road_condition', 'weather_condition']);
        });
    }
};
