<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Lets a device prove it is the device, instead of merely claiming to be.
 *
 * Until now a report carried only its device_code, which is not a secret: it
 * is printed on the case, shown on the TOC screen and sent with every
 * request. Anyone who knew one could file a crash as that device.
 *
 * signing_secret is shared once, by hand, over the USB cable during
 * provisioning, and then never travels again — the device signs each report
 * with it and the server recomputes the signature. That holds even on plain
 * HTTP, which is where STEP 1 in the firmware's deployment.h is heading.
 *
 * last_signature_counter is replay protection. The device has no clock, so a
 * timestamp is not available to it; it sends a counter that only ever goes up
 * instead, and the server refuses anything it has already seen.
 *
 * signature_required_since is what makes this safe to deploy to devices
 * already in the field. It stays null until the server sees this device's
 * first correctly signed request; from that moment unsigned reports from it
 * are refused. So a device that has not been provisioned yet keeps working,
 * and one that has can never be downgraded to unsigned.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('signing_secret', 64)->nullable()->after('pairing_key');
            $table->unsignedBigInteger('last_signature_counter')->default(0)->after('signing_secret');
            $table->timestamp('signature_required_since')->nullable()->after('last_signature_counter');
        });

        // Existing rows get a secret too, so their provisioning command can be
        // shown on the device page without re-registering anything.
        foreach (\Illuminate\Support\Facades\DB::table('devices')->whereNull('signing_secret')->pluck('id') as $id) {
            \Illuminate\Support\Facades\DB::table('devices')
                ->where('id', $id)
                ->update(['signing_secret' => bin2hex(random_bytes(32))]);
        }
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['signing_secret', 'last_signature_counter', 'signature_required_since']);
        });
    }
};
