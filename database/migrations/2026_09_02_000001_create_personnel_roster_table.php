<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The authoritative source new patrol registrations are checked
        // against — populated by TOC staff from real PNP Urdaneta personnel
        // records, separately from (and before) any officer actually
        // registers. A registration's badge_number must match an active row
        // here, closing the gap where a badge number was previously just
        // typed in by whichever admin approved the request, with nothing to
        // check it against.
        Schema::create('personnel_roster', function (Blueprint $table) {
            $table->id();
            $table->string('badge_number')->unique();
            $table->string('full_name');
            $table->string('rank');
            // Reference photo TOC staff compare an applicant's registration
            // photo against during review — optional, since a roster entry
            // is still useful (matches on badge number alone) even before a
            // photo has been added for that officer.
            $table->string('reference_photo_path')->nullable();
            // Soft-disable instead of deleting — an officer who transfers
            // out or is decommissioned shouldn't be able to be re-registered
            // against, but the historical roster record (and any past
            // registration that referenced it) should still resolve.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_roster');
    }
};
