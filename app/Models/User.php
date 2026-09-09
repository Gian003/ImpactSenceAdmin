<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone_number',
        'address',
        'profile_photo',
        'date_of_birth',
        'fcm_token',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'password'      => 'hashed',
        ];
    }

    public function device(): HasOne
    {
        return $this->hasOne(Device::class, 'rider_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'rider_id');
    }

    /**
     * Rider names are stored as one assembled string, and a missing part can
     * leave a literal "N/A" sitting inside it — which the spoken crash alert
     * read out loud and the IRF would print onto an official form. The
     * cleanup lives here so both callers share one definition of it.
     */
    public static function cleanName(?string $name): string
    {
        $name = preg_replace('/\b(n\/a|not applicable|null)\b/i', '', trim((string) $name));

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * Split into the family / first / middle boxes the PNP IRF asks for.
     *
     * Filipino convention: given name first, surname last, anything between
     * treated as the middle name. Returns empty strings rather than nulls so
     * an unknown part leaves the form field blank.
     *
     * @return array{family: string, first: string, middle: string}
     */
    public function nameParts(): array
    {
        $parts = array_values(array_filter(explode(' ', self::cleanName($this->full_name))));

        if (! $parts) {
            return ['family' => '', 'first' => '', 'middle' => ''];
        }

        // A single token is a given name, not a surname — better to leave the
        // family-name box empty than to file someone under their first name.
        if (count($parts) === 1) {
            return ['family' => '', 'first' => $parts[0], 'middle' => ''];
        }

        $first  = array_shift($parts);
        $family = array_pop($parts);

        return [
            'family' => $family,
            'first'  => $first,
            'middle' => implode(' ', $parts),
        ];
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class, 'rider_id');
    }
}
