<?php

use App\Http\Controllers\Admin\StaffController;
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

        Route::get('/staff', [StaffController::class, 'index'])
            ->name('staff.index');

        Route::get('/staff/create', [StaffController::class, 'create'])
            ->name('staff.create');

        Route::post('/staff', [StaffController::class, 'store'])
            ->name('staff.store');

        Route::get('/staff/{user}/edit', [StaffController::class, 'edit'])
            ->name('staff.edit');

        Route::put('/staff/{user}', [StaffController::class, 'update'])
            ->name('staff.update');

        Route::patch('/staff/{user}/status', [StaffController::class, 'updateStatus'])
            ->name('staff.status');
    });
