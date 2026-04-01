<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionaleAuthController;
use App\Http\Controllers\GestionaleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Rotta principale - Mostra il form di login
Route::get('/', function () {
    return view('welcome');
})->name('login');

// Rotte di autenticazione per il gestionale
Route::controller(GestionaleAuthController::class)->group(function () {
    // Elabora il login
    Route::post('/login', 'login')->name('gestionale.login');
    
    // Logout
    Route::post('/logout', 'logout')->name('gestionale.logout');
});

// Rotte protette da autenticazione (richiedono login)
Route::middleware(['auth'])->group(function () {
    // Dashboard principale del gestionale
    Route::get('/dashboard', [GestionaleController::class, 'dashboard'])
        ->name('gestionale.dashboard');
    
    // Altre rotte protette (aggiungi qui le tue rotte future)
    // Route::get('/utenti', [GestionaleController::class, 'users'])->name('gestionale.users');
    // Route::get('/report', [GestionaleController::class, 'reports'])->name('gestionale.reports');
    // Route::get('/impostazioni', [GestionaleController::class, 'settings'])->name('gestionale.settings');
});

// Rotta di test (opzionale - da rimuovere in produzione)
if (app()->environment('local')) {
    Route::get('/test', function () {
        return view('welcome');
    });
}