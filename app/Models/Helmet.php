<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
