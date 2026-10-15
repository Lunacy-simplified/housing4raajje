<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::get('/', fn() => 'Owner dashboard (placeholder)')
            ->name('dashboard');
    });
