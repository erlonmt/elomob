<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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
Route::get('/resultados', function () {
    return view('resultados');
})->name('resultados');

// Rota de Detalhes da Viagem
Route::get('/detalhes-viagem', function () {
    return view('detalhes-viagem');
})->name('detalhes-viagem');

// 👇 NOVA ROTA DO HISTÓRICO 👇
Route::get('/historico', function () {
    return view('historico');
})->name('historico');
// 👆 FIM DA NOVA ROTA 👆

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';