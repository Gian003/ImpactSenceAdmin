<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('helmets', function (Blueprint $table) {
            // device_code stays public (like a serial number printed on the box) -
            // pairing_key is the actual secret, high-entropy credential that gates
            // pairing, so a guessed/enumerated device_code alone can't hijack a device.
            $table->string('pairing_key', 8)->nullable()->unique()->after('device_code');
        });

        // Backfill any existing helmets (created before this column existed)
        // with a random key so they aren't left permanently unpairable.
        DB::table('helmets')->whereNull('pairing_key')->get()->each(function ($helmet) {
            DB::table('helmets')->where('id', $helmet->id)->update([
                'pairing_key' => strtoupper(Str::random(8)),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('helmets', function (Blueprint $table) {
            $table->dropColumn('pairing_key');
        });
    }
};
