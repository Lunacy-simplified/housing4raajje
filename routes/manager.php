<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:branch_manager'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {
        Route::view(
            '/',
            'dashboard-placeholder',
            ['portal' => 'Branch Manager']
        )
            ->name('dashboard');
    });
