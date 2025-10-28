<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SmtpController;
use App\Http\Controllers\BackupsController;
use App\Http\Controllers\ServidoresController;
use App\Http\Controllers\AplicacoesController;
use App\Http\Controllers\TiposController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('web')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::middleware('session.auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/smtp', [SmtpController::class, 'index'])->name('smtp.index');
        Route::get('/backups', [BackupsController::class, 'index'])->name('backups.index');
        Route::get('/servidores', [ServidoresController::class, 'index'])->name('servidores.index');
        Route::get('/aplicacoes', [AplicacoesController::class, 'index'])->name('aplicacoes.index');
        Route::get('/tipos', [TiposController::class, 'index'])->name('tipos.index');
    });
});
