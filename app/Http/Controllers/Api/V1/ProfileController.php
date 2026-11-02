<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\SessionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Show the signed-in account and its preferences.
     */
    public function show(Request $request): ProfileResource
    {
        return new ProfileResource($request->user());
    }

    /**
     * Update the signed-in account and its preferences.
     */
    public function update(UpdateProfileRequest $request): ProfileResource
    {
        $request->user()->update($request->profile());

        return new ProfileResource($request->user());
    }

    /**
     * Delete the signed-in account with its favorites and its searches, which would otherwise outlive it unlinked
     * from any account, and sign out. Signing out comes first, since it saves a new remember token on the account.
     */
    public function destroy(DeleteAccountRequest $request): SessionResource
    {
        $user = $request->user();
        Auth::logout();
        DB::transaction(function () use ($user): void {
            $user->tripSearches()->delete();
            $user->delete();
        });
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new SessionResource(null);
    }
}
