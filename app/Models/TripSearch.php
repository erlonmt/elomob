<?php

namespace App\Models;

use Database\Factories\TripSearchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A route search. Searches made by guests have no user and are tied to the browser session through
 * "session_hash", the SHA-256 of the random key kept in that session (never the key itself); they are
 * deleted a week after they are made (see prunable()). "departure_at" is the requested departure, in UTC; it is
 * null when the trip leaves at the time of the search (created_at). "filters" holds the options it was planned with.
 */
#[Fillable(['user_id', 'session_hash', 'origin_id', 'destination_id', 'departure_at', 'filters'])]
#[Hidden(['session_hash'])]
class TripSearch extends Model
{
    /** @use HasFactory<TripSearchFactory> */
    use HasFactory, MassPrunable;

    /**
     * Session entry holding the random key that ties guest searches to the browser session.
     */
    public const string SESSION_KEY = 'mobility_key';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'origin_id' => 'integer',
            'destination_id' => 'integer',
            'departure_at' => 'datetime',
            'filters' => 'array',
        ];
    }

    /**
     * Hash a session key the same way it is stored in "session_hash".
     */
    public static function hashSessionKey(string $sessionKey): string
    {
        return hash('sha256', $sessionKey);
    }

    /**
     * Determine whether this is a guest search created by the session holding the given key.
     */
    public function belongsToSession(?string $sessionKey): bool
    {
        return $this->user_id === null
            && $sessionKey !== null
            && hash_equals($this->session_hash, self::hashSessionKey($sessionKey));
    }

    /**
     * Get the searches "model:prune" deletes: guest searches older than a week. Only the session that made a guest
     * search can open it again, and sessions expire after two idle hours, so these are not kept as history; the
     * foreign keys delete their itineraries and steps with them. Searches of an account stay in its history.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->whereNull('user_id')->where('created_at', '<', now()->subWeek());
    }

    /**
     * Get the account that made the search, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the stop where the trip starts.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'origin_id');
    }

    /**
     * Get the stop where the trip ends.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'destination_id');
    }

    /**
     * Get the itineraries found for the search, one per criterion, in creation order.
     *
     * @return HasMany<Itinerary, $this>
     */
    public function itineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class)->orderBy('id');
    }
}
