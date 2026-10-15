<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:client'])
    ->prefix('client')
    ->name('client.')
    ->group(function () {
        Route::get('/', fn () => 'Client dashboard (placeholder)')
            ->name('dashboard');
    });