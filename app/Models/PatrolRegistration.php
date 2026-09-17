<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolRegistration extends Model
{
    use HasFactory;

    /**
     * The reasons a registration is normally turned down.
     *
     * Kept here rather than only in the Blade so the server can check that a
     * submitted reason is one of them — a dropdown alone is a suggestion, not
     * a constraint. Worded as the applicant will read them: the text is sent
     * to their phone verbatim in the rejection notification, so each one has
     * to stand on its own without the reviewer adding anything.
     *
     * They follow the checks a reviewer actually performs against the
     * personnel roster, in the order those checks fail most often.
     */
    public const REJECTION_REASONS = [
        'Badge number not found in the personnel roster.',
        'Name does not match the roster record for this badge number.',
        'Rank does not match the roster record for this badge number.',
        'Photograph is unclear or does not match the roster reference photo.',
        'Officer is no longer assigned to this station.',
        'An account already exists for this badge number.',
        'Submitted details are incomplete or contain errors.',
    ];

    /** Value used by the form when the reviewer writes their own reason. */
    public const REJECTION_OTHER = 'other';

    // Where the registration photo came from. Null on registrations made
    // before gallery uploads were allowed — those were all camera captures.
    public const PHOTO_CAMERA  = 'camera';
    public const PHOTO_GALLERY = 'gallery';

    /** True unless the applicant chose the photo from their gallery. */
    public function photoWasTakenLive(): bool
    {
        return $this->photo_source !== self::PHOTO_GALLERY;
    }

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'status',
        'rejection_reason',
        'badge_number',
        'rank',
        'photo_path',
        'photo_source',
        'reviewed_by',
        'reviewed_at',
        'fcm_token',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'reviewed_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(TocPersonnel::class, 'reviewed_by');
    }

    // Joined on badge_number (not a foreign key column) — the roster is the
    // authoritative source badge_number was validated against at submission
    // time, so this lets TOC review show the applicant's photo next to the
    // roster's reference photo for the same badge.
    public function roster(): BelongsTo
    {
        return $this->belongsTo(PersonnelRoster::class, 'badge_number', 'badge_number');
    }
}
