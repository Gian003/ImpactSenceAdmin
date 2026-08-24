<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helmets', function (Blueprint $table) {
            // Rider-supplied phone number of the SIM card inserted in the device.
            // Used by the IoT firmware as the SMS "from" number for direct SIM800L
            // fallback alerts, and displayed in the admin panel to identify the SIM.
            // nullable: many riders won't know their SIM number (Philippine prepaid
            // SIMs don't store it on-chip), so this is always optional.
            $table->string('sim_phone_number', 20)->nullable()->after('pairing_key');
        });
    }

    public function down(): void
    {
        Schema::table('helmets', function (Blueprint $table) {
            $table->dropColumn('sim_phone_number');
        });
    }
};
