<?php

namespace App\Policies;

use App\Models\TripSearch;
use App\Models\User;

class TripSearchPolicy
{
    /**
     * Determine whether the visitor can view the search: a signed-in search only by its account,
     * a guest search only by the browser session that created it (identified by its mobility key).
     */
    public function view(?User $user, TripSearch $tripSearch, ?string $sessionKey = null): bool
    {
        if ($tripSearch->user_id !== null) {
            return $user !== null && $tripSearch->user_id === $user->getKey();
        }

        return $tripSearch->belongsToSession($sessionKey);
    }
}
