<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turns a speed zone from a circle into a road corridor.
     *
     * A posted speed limit is a property of a road, not of a place, and a
     * circle could not express that. A 150 m circle around a point on the
     * Urdaneta bypass covers 7.1 hectares of which about 3% is the road it
     * was meant to measure — every GPS sample in the other 97%, on any other
     * street, was being averaged into that road's figure and judged against
     * its limit.
     *
     * `path` holds the road centreline as an ordered list of {lat, lng}
     * points; `radius_meters` keeps its name but now means the corridor's
     * half-width. A point is inside the zone when its distance to the
     * *polyline* is within that half-width.
     *
     * A one-point path is exactly a circle — distance to a single point is
     * distance to a centre — so existing zones migrate to a degenerate
     * corridor and behave precisely as they did before. Nothing has to be
     * redrawn until someone wants to.
     */
    public function up(): void
    {
        Schema::table('speed_zones', function (Blueprint $table) {
            $table->json('path')->nullable()->after('longitude');
        });

        // Seed each existing zone with its own centre as a single-point path.
        foreach (DB::table('speed_zones')->whereNull('path')->get() as $zone) {
            DB::table('speed_zones')->where('id', $zone->id)->update([
                'path' => json_encode([[
                    'lat' => (float) $zone->latitude,
                    'lng' => (float) $zone->longitude,
                ]]),
            ]);
        }
    }

    public function down(): void
    {
        // latitude/longitude were kept in sync with the path all along — they
        // hold the corridor's midpoint — so dropping the column loses shape
        // but never leaves a zone without a location.
        Schema::table('speed_zones', function (Blueprint $table) {
            $table->dropColumn('path');
        });
    }
};
