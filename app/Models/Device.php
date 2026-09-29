<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Device extends Model
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
        'sim_phone_number',
    ];

    // Never serialised. The rider's own device endpoint returns this model
    // whole, and the signing secret is the one field that must not travel —
    // it is what proves a report came from this hardware.
    protected $hidden = [
        'signing_secret',
    ];

    // pairing_key and signing_secret are deliberately NOT fillable — they must
    // only ever be set by this auto-generation, never by mass assignment from
    // a request.
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Device $device) {
            if (empty($device->pairing_key)) {
                $device->pairing_key = strtoupper(Str::random(8));
            }

            // 256 bits from the CSPRNG: long enough that guessing is not a
            // strategy, short enough to paste into a serial monitor.
            //
            // Only once the column exists. This code can reach a server before
            // its migration does, and registering a device must not start
            // failing because of a column the operator has not added yet.
            if (empty($device->signing_secret) && self::signingSupported()) {
                $device->signing_secret = bin2hex(random_bytes(32));
            }
        });
    }

    /**
     * Whether this database can hold signing secrets yet.
     *
     * Deliberately not memoised: a static would be cached for the life of the
     * process, which in tests means the first database checked decides the
     * answer for every one after it. A column lookup is a cheap metadata query
     * and this runs on device registration and device requests only.
     */
    public static function signingSupported(): bool
    {
        return Schema::hasColumn('devices', 'signing_secret');
    }

    /** The line TOC pastes into the serial monitor to provision this unit. */
    public function provisioningCommand(): string
    {
        return 'SECRET ' . $this->signing_secret;
    }

    /** True once this device has proved it can sign, so unsigned is refused. */
    public function requiresSignature(): bool
    {
        return $this->signature_required_since !== null;
    }

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'paired_at'  => 'datetime',
            'signature_required_since' => 'datetime',
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
