<?php

use App\Http\Controllers\Auth\EmailVerificationController;
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

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [
        EmailVerificationController::class,
        'notice',
    ])->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [
        EmailVerificationController::class,
        'verify',
    ])->middleware(['signed', 'throttle:6,1'])
      ->name('verification.verify');

    Route::post('/email/verification-notification', [
        EmailVerificationController::class,
        'send',
    ])->middleware('throttle:6,1')
      ->name('verification.send');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

