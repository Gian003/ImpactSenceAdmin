<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Helmet extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_code',
        'rider_id',
        'model',
        'firmware_version',
        'battery_level',
        'is_active',
        'paired_at',
    ];

    // pairing_key is deliberately NOT fillable - it must only ever be set by
    // this auto-generation, never by mass assignment from a request.
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Helmet $helmet) {
            if (empty($helmet->pairing_key)) {
                $helmet->pairing_key = strtoupper(Str::random(8));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'paired_at'  => 'datetime',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
