<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Autenticação por token (Laravel Sanctum) para o front-end e o app.
 */
class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']));

        return $this->tokenResponse($user, $request->input('device_name'), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }

        return $this->tokenResponse($user, $request->input('device_name'));
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    private function tokenResponse(User $user, ?string $deviceName, int $status = 200): JsonResponse
    {
        $token = $user->createToken($deviceName ?: 'web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->refresh()),
        ], $status);
    }
}
