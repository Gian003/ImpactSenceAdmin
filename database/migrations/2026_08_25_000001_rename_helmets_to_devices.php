<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('helmets', 'devices');

        Schema::table('incidents', function (Blueprint $table) {
            $table->renameColumn('helmet_id', 'device_id');
        });

        Schema::table('speed_reports', function (Blueprint $table) {
            $table->renameColumn('helmet_id', 'device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('speed_reports', function (Blueprint $table) {
            $table->renameColumn('device_id', 'helmet_id');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->renameColumn('device_id', 'helmet_id');
        });

        Schema::rename('devices', 'helmets');
    }
};
