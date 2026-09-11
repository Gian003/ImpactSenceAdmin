<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What actually happened to an incident, as it happened.
     *
     * The detail page's timeline was inferred from four mutable columns —
     * created_at, dispatched_at, arrived_at, resolved_at — which can hold
     * exactly one of each event and nothing about who caused it. Three
     * consequences, all live in the data today:
     *
     *   - No actor anywhere. Which operator dispatched, who closed the call:
     *     no column existed to answer it.
     *   - Nine false-alarm incidents have no time at all, because cancelling
     *     an alert had nowhere to write to.
     *   - Reassignment rewrote the past. The TOC dispatch route set
     *     dispatched_at = now() unconditionally, so sending a second unit
     *     destroyed the first dispatch and the timeline then showed the new
     *     unit at the new time as though nothing else had occurred.
     *
     * Rows here are append-only. Nothing updates or deletes them; a
     * correction is another row. That is the whole point: an inferred
     * timeline changes when someone edits the incident, so a reassignment
     * today rewrites what the record says about yesterday. This cannot be
     * rewritten by a later edit — the same reasoning as
     * incident_field_reports.
     */
    public function up(): void
    {
        Schema::create('incident_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();

            // reported, dispatched, reassigned, arrived, resolved, cancelled,
            // reopened, field_report_filed, note_added.
            $table->string('type', 40);

            $table->string('status_from', 20)->nullable();
            $table->string('status_to', 20)->nullable();

            // Who did it: toc, investigation, patrol, rider or system.
            $table->string('actor_type', 20)->nullable();

            // Deliberately NOT a foreign key. An audit trail must not lose
            // entries because the person who made them left the force.
            $table->unsignedBigInteger('actor_id')->nullable();

            // Snapshot of the name at the time, for the same reason. Who the
            // record says acted must not change when a profile is edited.
            $table->string('actor_name')->nullable();

            $table->json('payload')->nullable();

            // When it happened, which is not always when the row was written —
            // backfilled events carry the original timestamp.
            $table->timestamp('occurred_at');

            // Reconstructed from timestamp columns rather than logged live.
            // Shown as such, because presenting a guess as a record is worse
            // than admitting the gap.
            $table->boolean('reconstructed')->default(false);

            $table->timestamps();

            $table->index(['incident_id', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_events');
    }
};
