<?php

namespace App\Models;

use App\Enums\RouteCriterion;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'preferred_route', 'transport_modes', 'accessibility_needs',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'preferred_route' => 'fastest',
        'accessibility_needs' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferred_route' => RouteCriterion::class,
            'transport_modes' => 'array',
            'accessibility_needs' => 'array',
        ];
    }

    public function savedPlaces(): HasMany
    {
        return $this->hasMany(SavedPlace::class);
    }

    public function tripSearches(): HasMany
    {
        return $this->hasMany(TripSearch::class);
    }
}
