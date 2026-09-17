<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PatrolUnit extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // The patrol app pushes its position every 30 seconds while open, so a
    // unit that hasn't checked in for four of those ticks is treated as
    // offline. Generous on purpose: a couple of dropped requests on a weak
    // signal shouldn't flip an on-duty officer to offline on the TOC roster.
    public const ONLINE_WITHIN_MINUTES = 2;

    protected $fillable = [
        'full_name',
        'badge_number',
        'email',
        'password',
        'rank',
        'mobile_number',
        'current_latitude',
        'current_longitude',
        'last_seen_at',
        'status',
        'on_duty',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'current_latitude'  => 'decimal:7',
            'current_longitude' => 'decimal:7',
            'last_seen_at'      => 'datetime',
            'on_duty'           => 'boolean',
            'password'          => 'hashed',
        ];
    }

    // Whether this unit may be asked to take a new call right now. All four
    // have to hold, and each one catches a different way of alerting the
    // wrong person:
    //   on_duty       the officer has said they are taking calls
    //   isOnline()    the app is actually reporting in, so the position is live
    //   not dispatched  they are not already on another call
    //   coordinates   there is a position to measure distance from at all
    public function isFreeToRespond(): bool
    {
        return $this->on_duty
            && $this->isOnline()
            && $this->status !== 'dispatched'
            && $this->current_latitude !== null
            && $this->current_longitude !== null;
    }

    // What `status` should read when a unit is not on a call: free if the
    // officer is on shift, off duty if not. Finishing a call used to reset
    // every unit to off_duty regardless.
    public function idleStatus(): string
    {
        return $this->on_duty ? 'available' : 'off_duty';
    }

    // Presence, which is a separate question from `status`: this asks whether
    // the phone is reporting in, `status` asks whether the officer is tied up
    // on a call. A unit can be online and free, online and dispatched, or
    // offline with a stale last known position.
    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subMinutes(self::ONLINE_WITHIN_MINUTES));
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
