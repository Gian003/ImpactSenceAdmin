<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('helmet_id')->nullable()->constrained('helmets')->nullOnDelete();
            $table->foreignId('patrol_unit_id')->nullable()->constrained('patrol_units')->nullOnDelete();
            $table->string('type')->default('collision');      // collision | fall | other
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('high');
            $table->enum('status', ['pending', 'dispatched', 'resolved', 'false_alarm'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
