<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/mfa', [MfaController::class, 'show'])->name('mfa.show');
    Route::post('/mfa', [MfaController::class, 'verify'])->middleware('throttle:5,1')->name('mfa.verify');
    Route::post('/mfa/resend', [MfaController::class, 'resend'])->middleware('throttle:3,1')->name('mfa.resend');

    Route::get('/forgot-password', [
        PasswordResetController::class,
        'request',
    ])->name('password.request');

    Route::post('/forgot-password', [
        PasswordResetController::class,
        'email',
    ])->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [
        PasswordResetController::class,
        'edit',
    ])->name('password.reset');

    Route::post('/reset-password', [
        PasswordResetController::class,
        'update',
    ])->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('/register/{type}', [RegisterController::class, 'create'])
        ->whereIn('type', ['client', 'owner'])
        ->name('register');
    Route::post('/register/{type}', [RegisterController::class, 'store'])
        ->whereIn('type', ['client', 'owner'])
        ->middleware('throttle:10,1');

    Route::get('/staff-invite/{token}', [
        PasswordResetController::class,
        'showInvite',
    ])->name('staff-invite.show');

    Route::post('/staff-invite', [
        PasswordResetController::class,
        'acceptInvite',
    ])
        ->middleware('throttle:6,1')
        ->name('staff-invite.accept');
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
