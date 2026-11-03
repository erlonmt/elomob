<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RouteCriterion;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'preferred_route', 'transport_modes', 'accessibility_needs'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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

    /**
     * Keep the e-mail in lower case, so an account is found and kept unique whatever case its e-mail is typed in
     * (the database also refuses two e-mails differing only in case).
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => Str::lower($value));
    }

    /**
     * Get the places the user saved as favorites.
     *
     * @return HasMany<SavedPlace, $this>
     */
    public function savedPlaces(): HasMany
    {
        return $this->hasMany(SavedPlace::class);
    }

    /**
     * Get the route searches the user made while signed in.
     *
     * @return HasMany<TripSearch, $this>
     */
    public function tripSearches(): HasMany
    {
        return $this->hasMany(TripSearch::class);
    }
}
