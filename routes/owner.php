<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::view(
            '/',
            'dashboard-placeholder',
            ['portal' => 'Owner']
        )
            ->name('dashboard');
    });
