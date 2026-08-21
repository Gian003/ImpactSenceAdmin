<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminInvitation extends Model
{
    protected $fillable = [
        'invited_by',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'  => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function invitedBy()
    {
        return $this->belongsTo(Admin::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return is_null($this->accepted_at) && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return is_null($this->accepted_at) && $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return !is_null($this->accepted_at);
    }

    public function statusLabel(): string
    {
        if ($this->isAccepted()) return 'Accepted';
        if ($this->isExpired())  return 'Expired';
        return 'Pending';
    }
}
