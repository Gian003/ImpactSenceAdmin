<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toc_personnel', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('badge_number')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('rank');
            $table->string('unit_assignment')->nullable();
            $table->enum('role', ['toc_officer', 'supervisor'])->default('toc_officer');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toc_personnel');
    }
};
