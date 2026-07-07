<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'helmet_id',
        'latitude',
        'longitude',
        'speed_kph',
    ];

    protected function casts(): array
    {
        return [
            'latitude'  => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function helmet(): BelongsTo
    {
        return $this->belongsTo(Helmet::class);
    }
}
