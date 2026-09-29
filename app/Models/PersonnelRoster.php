<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PersonnelRoster extends Model
{
    use HasFactory;

    protected $table = 'personnel_roster';

    /**
     * Official PNP rank classification under RA 11200 (2019), highest to
     * lowest, plus Non-Uniformed Personnel (NUP) — civilian PNP employees in
     * administrative/technical/clerical roles who fall outside the RA 11200
     * ladder entirely. NUP position titles (Administrative Aide, Records
     * Officer, Computer Operator, etc.) are a large, office-specific Civil
     * Service classification, not a small closed list like the uniformed
     * ranks above, so NUP is represented here as one category rather than
     * enumerated position-by-position.
     *
     * Kept as a fixed list (not free text) so roster entries can't drift
     * from real PNP naming/abbreviations — single source of truth for both
     * the Add Personnel dropdown and the store() validation rule.
     */
    public const RANKS = [
        'Police General',
        'Police Lieutenant General',
        'Police Major General',
        'Police Brigadier General',
        'Police Colonel',
        'Police Lieutenant Colonel',
        'Police Major',
        'Police Captain',
        'Police Lieutenant',
        'Police Executive Master Sergeant',
        'Police Chief Master Sergeant',
        'Police Senior Master Sergeant',
        'Police Master Sergeant',
        'Police Staff Sergeant',
        'Police Corporal',
        'Patrolman/Patrolwoman',
        'Non-Uniformed Personnel (NUP)',
    ];

    /**
     * A PNP officer's identification number is six digits. The app used to ask
     * for "PNP-12345", which is not what is printed on an officer's ID — that
     * prefixed style belongs to a handful of senior posts, not to the patrol
     * officers who use this system.
     *
     * Kept here, with the roster, because the roster is the authoritative list
     * every other badge field is checked against.
     */
    public const BADGE_DIGITS = 6;

    /** Validation rules for a badge number, shared by every form that takes one. */
    public static function badgeRules(): array
    {
        return ['required', 'string', 'digits:' . self::BADGE_DIGITS];
    }

    /** One wording for the error, wherever it is raised. */
    public static function badgeMessages(string $field = 'badge_number'): array
    {
        return [
            $field . '.digits' => 'A badge number is the officer\'s '
                . self::BADGE_DIGITS . '-digit PNP ID — digits only, no letters or dashes.',
            $field . '.required' => 'Enter the officer\'s '
                . self::BADGE_DIGITS . '-digit PNP ID.',
        ];
    }

    protected $fillable = [
        'badge_number',
        'full_name',
        'rank',
        'reference_photo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
