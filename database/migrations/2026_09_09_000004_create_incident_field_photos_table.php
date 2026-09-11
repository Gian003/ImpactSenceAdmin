<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scene photographs attached to a field report.
     *
     * Stored on the private disk, never `public`. These frames contain
     * injured people, faces and plate numbers, and the dashboard is served
     * over a public tunnel — an unguessable URL is not access control. They
     * are read back only through an authenticated route.
     *
     * The metadata columns are what separates a photograph from evidence:
     * sha256 lets anyone show the file on disk is the file that was uploaded,
     * and captured_at records when the camera fired rather than when the
     * upload happened to arrive, which can be much later if the responder had
     * no signal at the scene.
     */
    public function up(): void
    {
        Schema::create('incident_field_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_field_report_id')
                ->constrained()->cascadeOnDelete();

            $table->string('path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();

            // Hex sha-256 of the stored bytes — fixed 64 chars.
            $table->string('sha256', 64)->nullable();

            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_field_photos');
    }
};
