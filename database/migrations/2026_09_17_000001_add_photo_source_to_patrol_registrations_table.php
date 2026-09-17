<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where the applicant's registration photo came from: 'camera' or
     * 'gallery'.
     *
     * The app used to allow the camera only, so that an applicant had to
     * photograph themselves in the moment rather than submit an old or
     * borrowed picture. The client asked for gallery uploads as well. This
     * column keeps that information for the TOC reviewer, who can then look
     * harder at a gallery photo instead of assuming every photo is live.
     *
     * Nullable: registrations made before this change have no value, and all
     * of them were camera captures.
     */
    public function up(): void
    {
        Schema::table('patrol_registrations', function (Blueprint $table) {
            $table->string('photo_source', 10)->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('patrol_registrations', function (Blueprint $table) {
            $table->dropColumn('photo_source');
        });
    }
};
