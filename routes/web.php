<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
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

    Route::get('/phone/verify', [PhoneVerificationController::class, 'show'])
        ->name('phone.verification.show');

    Route::post('/phone/verification-code', [PhoneVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('phone.verification.send');

    Route::post('/phone/verify', [PhoneVerificationController::class, 'verify'])
        ->middleware('throttle:5,1')
        ->name('phone.verification.verify');
    
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Pages every signed-in role shares.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/photos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
});

// Conversations: owners and clients take part; branch staff supervise (not administrators).
Route::middleware(['auth', 'role:owner,client,property_specialist,receptionist,branch_manager'])->group(function () {
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{thread}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{thread}', [MessageController::class, 'reply'])->middleware('throttle:30,1')->name('messages.reply');
});

Route::middleware(['auth', 'role:owner,client'])->group(function () {
    Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:10,1')->name('messages.store');

    Route::get('/leases', [LeaseController::class, 'index'])->name('leases.index');
    Route::get('/leases/{lease}', [LeaseController::class, 'show'])->name('leases.show');
    Route::post('/leases/{lease}/sign', [LeaseController::class, 'sign'])->middleware('throttle:5,1')->name('leases.sign');

    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
});
