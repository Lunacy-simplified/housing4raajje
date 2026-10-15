<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:branch_manager'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {
        Route::get('/', fn() => 'Manager dashboard (placeholder)')
            ->name('dashboard');
    });
