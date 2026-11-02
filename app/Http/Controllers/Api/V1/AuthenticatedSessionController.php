<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\SessionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the visitor's session: the signed-in account, if any, and the CSRF token.
     */
    public function show(Request $request): SessionResource
    {
        return new SessionResource($request->user());
    }

    /**
     * Sign in with an e-mail and a password, renewing the session id so it cannot be fixed beforehand.
     */
    public function store(LoginRequest $request): SessionResource
    {
        $request->authenticate();
        $request->session()->regenerate();

        return new SessionResource($request->user());
    }

    /**
     * Sign out, discarding the session and its CSRF token.
     */
    public function destroy(Request $request): SessionResource
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new SessionResource(null);
    }
}
