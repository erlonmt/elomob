<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TripSearchController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rotas das Páginas Separadas
Route::get('/como-funciona', function () {
    return view('como-funciona');
})->name('como-funciona');

Route::get('/servicos', function () {
    return view('servicos');
})->name('servicos');

Route::get('/porque', function () {
    return view('porque');
})->name('porque');

// Rota de Resultados
Route::get('/resultados', [TripSearchController::class, 'search'])->name('resultados');

// Rota de Detalhes da Viagem
Route::get('/detalhes-viagem', function () {
    return view('detalhes-viagem');
})->name('detalhes-viagem');

// Rota do Histórico
Route::get('/historico', function () {
    return view('historico');
})->name('historico');

// 👇 NOVA ROTA DE TERMOS E PRIVACIDADE 👇
Route::get('/termos', function () {
    return view('termos');
})->name('termos');
// 👆 FIM DA NOVA ROTA 👆

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';