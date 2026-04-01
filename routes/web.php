<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionaleAuthController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', [GestionaleAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [GestionaleAuthController::class, 'login']);
Route::post('/logout', [GestionaleAuthController::class, 'logout'])->name('logout');
Route::get('/dashboard', [GestionaleAuthController::class, 'dashboard'])->name('dashboard')->middleware('auth');