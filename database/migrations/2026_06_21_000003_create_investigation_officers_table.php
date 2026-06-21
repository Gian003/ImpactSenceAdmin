<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_officers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('badge_number')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('rank');
            $table->string('unit_assignment')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_officers');
    }
};
