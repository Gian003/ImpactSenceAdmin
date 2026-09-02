<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrol_registrations', function (Blueprint $table) {
            // Live-captured (camera, not gallery) photo the applicant takes
            // during registration — shown next to the matching
            // personnel_roster entry's reference photo so TOC staff can
            // actually compare the two during review, instead of approving
            // based on a typed name alone.
            $table->string('photo_path')->nullable()->after('rank');
        });
    }

    public function down(): void
    {
        Schema::table('patrol_registrations', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
