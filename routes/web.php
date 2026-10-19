<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/register/{type}', [RegisterController::class, 'create'])
        ->whereIn('type', ['client', 'owner'])
        ->name('register');
    Route::post('/register/{type}', [RegisterController::class, 'store'])
        ->whereIn('type', ['client', 'owner'])
        ->middleware('throttle:10,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
