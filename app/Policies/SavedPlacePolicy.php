<?php

namespace App\Policies;

use App\Models\SavedPlace;
use App\Models\User;

class SavedPlacePolicy
{
    /**
     * Determine whether the user can delete the saved place: only the account that saved it.
     */
    public function delete(User $user, SavedPlace $savedPlace): bool
    {
        return $savedPlace->user_id === $user->id;
    }
}
