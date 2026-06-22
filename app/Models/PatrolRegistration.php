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
}
