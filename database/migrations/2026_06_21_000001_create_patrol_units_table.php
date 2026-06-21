<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrol_units', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('badge_number')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('rank');
            $table->string('mobile_number')->nullable();
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->enum('status', ['available', 'dispatched', 'off_duty'])->default('off_duty');
            $table->string('fcm_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrol_units');
    }
};
