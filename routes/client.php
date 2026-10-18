<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:client'])
    ->prefix('client')
    ->name('client.')
    ->group(function () {
        Route::view(
            '/',
            'dashboard-placeholder',
            ['portal' => 'Client']
        )
            ->name('dashboard');
    });
