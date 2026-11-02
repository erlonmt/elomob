<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\SessionResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    /**
     * Create an account and sign in with it. The account is read back from the database, so it carries the
     * preferences the database gives it by default and later responses about it are not marked as a creation.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']))->fresh();
        Auth::login($user);
        $request->session()->regenerate();

        return (new SessionResource($user))->response()->setStatusCode(201);
    }
}
