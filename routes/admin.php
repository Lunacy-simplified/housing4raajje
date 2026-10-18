<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:system_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view(
            '/',
            'dashboard-placeholder',
            ['portal' => 'Admin']
        )
            ->name('dashboard');
    });
