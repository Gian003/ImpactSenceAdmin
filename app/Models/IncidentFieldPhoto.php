<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One scene photograph. Lives on the private disk — see the migration.
 */
class IncidentFieldPhoto extends Model
{
    use HasFactory;

    /** The disk these are written to. Deliberately not 'public'. */
    public const DISK = 'local';

    protected $fillable = [
        'incident_field_report_id',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'sha256',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes'  => 'integer',
            'captured_at' => 'datetime',
        ];
    }

    public function fieldReport(): BelongsTo
    {
        return $this->belongsTo(IncidentFieldReport::class, 'incident_field_report_id');
    }

    public function exists(): bool
    {
        return Storage::disk(self::DISK)->exists($this->path);
    }

    /**
     * Re-hashes the file on disk and compares it to what was recorded at
     * upload. The point of storing a digest is being able to answer "is this
     * still the photograph that was taken?" — so there has to be something
     * that asks.
     */
    public function integrityIntact(): ?bool
    {
        if (! $this->sha256 || ! $this->exists()) {
            return null;
        }

        return hash_equals(
            $this->sha256,
            hash('sha256', Storage::disk(self::DISK)->get($this->path))
        );
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
