<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'rider_id',
        'helmet_id',
        'patrol_unit_id',
        'type',
        'latitude',
        'longitude',
        'address',
        'severity',
        'status',
        'notes',
        'dispatched_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'      => 'decimal:7',
            'longitude'     => 'decimal:7',
            'dispatched_at' => 'datetime',
            'resolved_at'   => 'datetime',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function helmet(): BelongsTo
    {
        return $this->belongsTo(Helmet::class);
    }

    public function patrolUnit(): BelongsTo
    {
        return $this->belongsTo(PatrolUnit::class);
    }

    public function incidentRecords(): HasMany
    {
        return $this->hasMany(IncidentRecord::class);
    }
}
