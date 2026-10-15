<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:property_specialist,receptionist'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/', fn() => 'Staff dashboard (placeholder)')
            ->name('dashboard');
    });
