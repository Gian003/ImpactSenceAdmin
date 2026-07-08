<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speed_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            // Circular zone radius — real GPS speed samples within this
            // distance of (latitude, longitude) count toward this zone.
            $table->unsignedInteger('radius_meters')->default(150);
            $table->unsignedSmallInteger('speed_limit_kph');
            $table->foreignId('created_by')->nullable()->constrained('toc_personnel')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speed_zones');
    }
};
