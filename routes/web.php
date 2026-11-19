<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Disciplina\BoletaArrestoController;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth'])
    ->prefix('disciplina')
    ->name('disciplina.')
    ->group(function () {
        Route::get(
            '/sanciones/{sancion}/boleta-arresto',
            BoletaArrestoController::class
        )->name('boleta-arresto');
    });
