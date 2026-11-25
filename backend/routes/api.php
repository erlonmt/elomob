<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LineController;
use App\Http\Controllers\Api\NetworkController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SavedPlaceController;
use App\Http\Controllers\Api\StopController;
use App\Http\Controllers\Api\TransportModeController;
use App\Http\Controllers\Api\TripSearchController;
use Illuminate\Support\Facades\Route;

// Público -------------------------------------------------------------------

Route::get('health', HealthController::class);

Route::get('transport-modes', [TransportModeController::class, 'index']);
Route::apiResource('stops', StopController::class)->only(['index', 'show']);
Route::apiResource('lines', LineController::class)->only(['index', 'show']);
Route::get('network', NetworkController::class);

// Busca funciona com ou sem conta; com token, aplica as preferências do perfil.
Route::post('trips', [TripSearchController::class, 'store'])->middleware('throttle:30,1');
Route::get('trips/{tripSearch}', [TripSearchController::class, 'show']);

Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Autenticado ---------------------------------------------------------------

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('me', [ProfileController::class, 'show']);
    Route::patch('me', [ProfileController::class, 'update']);
    Route::delete('me', [ProfileController::class, 'destroy']);

    Route::get('trips', [TripSearchController::class, 'index']);

    Route::apiResource('saved-places', SavedPlaceController::class)->only(['index', 'store', 'destroy']);
});
