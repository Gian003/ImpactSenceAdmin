<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_records', function (Blueprint $table) {
            $table->id();
            // Nullable — the IRF can be filled out and generated without a
            // linked incident (e.g. a walk-in report not yet in the system).
            $table->foreignId('incident_id')->nullable()->constrained('incidents')->nullOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('investigation_officers')->nullOnDelete();
            // Every named field on the IRF (~60 inputs across items A-E) as a
            // single JSON blob rather than one column per field — the form
            // isn't queried field-by-field, so a wide table would just be
            // migration churn every time a field is added/renamed.
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_records');
    }
};
