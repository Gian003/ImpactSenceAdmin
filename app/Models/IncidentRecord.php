<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'generated_by',
        'data',
        'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'data'       => 'array',
            'printed_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(InvestigationOfficer::class, 'generated_by');
    }
}
