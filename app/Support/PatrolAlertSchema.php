<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Whether the nearest-patrol migration (2026_09_18_000001) has run.
 *
 * The code that writes incidents.location_verified and patrol_units.on_duty
 * can reach a server before `php artisan migrate` does. Unguarded, the first
 * of those writes is the crash-report insert — so every crash report would
 * fail until someone ran the migration. Everything that writes the new
 * columns asks here first and carries on without them if they are missing.
 *
 * Checked once per request. PHP does not share statics between requests, so
 * a server that is migrated while running picks it up on the next one.
 */
final class PatrolAlertSchema
{
    private static ?bool $ready = null;

    public static function ready(): bool
    {
        return self::$ready ??= Schema::hasColumn('incidents', 'location_verified')
            && Schema::hasColumn('patrol_units', 'on_duty');
    }

    /** For tests that build their own schema. */
    public static function forget(): void
    {
        self::$ready = null;
    }
}
