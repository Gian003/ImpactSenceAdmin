<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolRegistration extends Model
{
    use HasFactory;

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
