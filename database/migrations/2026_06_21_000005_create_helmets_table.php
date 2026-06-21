<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helmets', function (Blueprint $table) {
            $table->id();
            $table->string('device_code')->unique();           // e.g. ITK-BLK4-GRP5-MDL1
            $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model')->nullable();
            $table->string('firmware_version')->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable(); // 0–100
            $table->boolean('is_active')->default(false);
            $table->timestamp('paired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helmets');
    }
};
