<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $user->update($request->validated());

        return new UserResource($user);
    }

    /**
     * Quando a própria pessoa exclui a conta, as buscas dela são apagadas na
     * mesma transação (sem isso, o banco só as desvincularia da conta).
     */
    public function destroy(Request $request): Response
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $user->tripSearches()->delete();
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->noContent();
    }
}
