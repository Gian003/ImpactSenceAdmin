<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TocPersonnel extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'toc_personnel';

    protected $fillable = [
        'full_name',
        'badge_number',
        'email',
        'password',
        'rank',
        'unit_assignment',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
