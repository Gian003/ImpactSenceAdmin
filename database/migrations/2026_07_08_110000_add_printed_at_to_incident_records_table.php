<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_records', function (Blueprint $table) {
            // Null = saved only. Set = "Save & Print" was used, whether at
            // initial generation or when a saved-only record is later
            // reopened and printed (see the store route).
            $table->timestamp('printed_at')->nullable()->after('data');
        });
    }

    public function down(): void
    {
        Schema::table('incident_records', function (Blueprint $table) {
            $table->dropColumn('printed_at');
        });
    }
};
