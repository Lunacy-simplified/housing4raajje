<?php

use App\Http\Controllers\Client\ApplicationController;
use App\Http\Controllers\Client\PropertyController;
use App\Http\Controllers\Client\ViewingController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:client'])
    ->prefix('client')
    ->name('client.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'client'])->name('dashboard');

        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');

        Route::get('/viewings', [ViewingController::class, 'index'])->name('viewings.index');
        Route::post('/properties/{property}/viewings', [ViewingController::class, 'store'])->name('viewings.store');
        Route::patch('/viewings/{viewing}/cancel', [ViewingController::class, 'cancel'])->name('viewings.cancel');

        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::patch('/applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])->name('applications.withdraw');

        // Applying for a property needs a verified email (auth spec 6.1).
        Route::post('/properties/{property}/applications', [ApplicationController::class, 'store'])
            ->middleware('verified')->name('applications.store');
    });
