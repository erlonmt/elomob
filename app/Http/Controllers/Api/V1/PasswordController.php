<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\ProfileResource;

class PasswordController extends Controller
{
    /**
     * Change the password of the signed-in account. This session goes on with the same CSRF token the frontend
     * holds, while sessions signed in with the old password are signed out on their next request (the
     * "auth.session" middleware).
     */
    public function update(UpdatePasswordRequest $request): ProfileResource
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return new ProfileResource($request->user());
    }
}
