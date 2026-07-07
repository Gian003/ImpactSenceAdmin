<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('helmets', function (Blueprint $table) {
            // One device per rider, enforced at the DB level. MySQL/InnoDB allows
            // multiple rows with rider_id = NULL under a unique index (NULL is never
            // considered equal to NULL), so unpaired helmets are unaffected - only a
            // second helmet trying to reuse the same non-null rider_id will be rejected.
            $table->unique('rider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('helmets', function (Blueprint $table) {
            $table->dropUnique(['rider_id']);
        });
    }
};
