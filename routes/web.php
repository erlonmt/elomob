<?php

use App\Http\Controllers\Api\V1\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ItineraryController;
use App\Http\Controllers\Api\V1\LineController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RegisteredUserController;
use App\Http\Controllers\Api\V1\SavedPlaceController;
use App\Http\Controllers\Api\V1\StopController;
use App\Http\Controllers\Api\V1\TripSearchController;
use App\Http\Middleware\PreventResponseCaching;
use Illuminate\Support\Facades\Route;

$notFound = fn () => abort(404);

Route::prefix('api/v1')->name('api.v1.')->group(function () use ($notFound) {
    Route::middleware('throttle:mobility-catalog')->group(function () {
        Route::get('/catalogo', CatalogController::class)->name('catalog');
        Route::get('/paradas', [StopController::class, 'index'])->name('stops');
        Route::get('/linhas', [LineController::class, 'index'])->name('lines');
    });

    Route::middleware([PreventResponseCaching::class, 'auth.session'])->group(function () use ($notFound) {
        Route::get('/sessao', [AuthenticatedSessionController::class, 'show'])->middleware('throttle:mobility-catalog')->name('session');
        Route::post('/entrar', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:mobility-auth')->name('login');
        Route::post('/cadastrar', [RegisteredUserController::class, 'store'])->middleware('throttle:mobility-auth')->name('register');
        Route::post('/buscas', [TripSearchController::class, 'store'])->middleware('throttle:mobility-search')->name('search.store');
        Route::get('/buscas/{tripSearch}', [TripSearchController::class, 'show'])->whereNumber('tripSearch')->missing($notFound)->name('search.show');
        Route::get('/trajetos/{itinerary}', [ItineraryController::class, 'show'])->whereNumber('itinerary')->missing($notFound)->name('itinerary');

        Route::middleware('auth')->group(function () use ($notFound) {
            Route::get('/perfil', [ProfileController::class, 'show'])->name('profile');
            Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/perfil', [ProfileController::class, 'destroy'])->middleware('throttle:mobility-password')->name('profile.delete');
            Route::put('/perfil/senha', [PasswordController::class, 'update'])->middleware('throttle:mobility-password')->name('password.update');
            Route::post('/sair', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
            Route::get('/favoritos', [SavedPlaceController::class, 'index'])->name('favorites');
            Route::post('/favoritos', [SavedPlaceController::class, 'store'])->name('favorites.store');
            Route::delete('/favoritos/{savedPlace}', [SavedPlaceController::class, 'destroy'])->whereNumber('savedPlace')->missing($notFound)->name('favorites.delete');
            Route::get('/historico', [TripSearchController::class, 'index'])->name('history');
            Route::delete('/historico', [TripSearchController::class, 'clear'])->name('history.clear');
        });
    });
});
