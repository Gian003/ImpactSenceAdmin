<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrol_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('password');                         // hashed
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();

            // Set by TOC admin on approval
            $table->string('badge_number')->nullable();
            $table->string('rank')->nullable();

            // Who reviewed it and when
            $table->foreignId('reviewed_by')->nullable()->constrained('toc_personnel')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // FCM token so we can push the result back to the officer's phone
            $table->string('fcm_token')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrol_registrations');
    }
};
