<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The responding patroller's own account of the scene.
     *
     * Deliberately a separate table rather than more columns on `incidents`
     * or another key in `incident_records.data`. The field report and the IRF
     * are two documents by two authors under different authority: the IRF is
     * signed by the investigator-on-case and the chief and becomes evidence,
     * while this is the first-hand account of whoever stood at the scene.
     * Folding one into the other would destroy the ability to say who
     * asserted what — the single thing an evidentiary record must preserve.
     *
     * Rows are append-only. A correction or an addition is a new row (a
     * supplemental report), never an edit of an existing one, so the sequence
     * of what was said and when survives intact.
     */
    public function up(): void
    {
        Schema::create('incident_field_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();

            // Nullable so a report outlives the deactivation of the unit that
            // filed it — losing the account because the author left the force
            // would be worse than an unattributed one.
            $table->foreignId('patrol_unit_id')->nullable()
                ->constrained()->nullOnDelete();

            $table->text('narrative')->nullable();

            // The four IRF fields the system genuinely cannot observe. They
            // already exist on `incidents` and already prefill the IRF form;
            // until now nothing at the scene ever filled them in.
            $table->unsignedTinyInteger('vehicles_involved')->nullable();
            $table->unsignedTinyInteger('injured_count')->nullable();
            $table->string('road_condition', 40)->nullable();
            $table->string('weather_condition', 40)->nullable();

            // Where the author actually was when they filed. Matching the
            // incidents table's precision so the two are directly comparable
            // — a report filed 8km from the scene is worth knowing about.
            $table->decimal('submitted_latitude', 10, 7)->nullable();
            $table->decimal('submitted_longitude', 10, 7)->nullable();

            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['incident_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_field_reports');
    }
};
