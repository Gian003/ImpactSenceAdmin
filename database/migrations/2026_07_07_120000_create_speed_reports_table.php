<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speed_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('helmet_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('speed_kph');
            $table->timestamps();

            // Speed-Reports-per-Area aggregates by rounding lat/lng, so lookups
            // scan recent rows for a given rounded location.
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speed_reports');
    }
};
