<?php

use App\Http\Controllers\Disciplina\BoletaDemeritosController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Disciplina\BoletaArrestoController;
use App\Http\Controllers\Disciplina\BoletaFirmadaController;

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

Route::get(
    '/sanciones/{sancion}/boleta-firmada',
    BoletaFirmadaController::class
)->name('disciplina.boleta-firmada');


Route::get(
    '/sanciones/{sancion}/boleta-demeritos',
    BoletaDemeritosController::class
)->name('disciplina.boleta-demeritos');
