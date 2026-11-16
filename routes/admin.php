<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:system_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');

        Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [PayoutController::class, 'generate'])->name('payouts.generate');
        Route::patch('/payouts/{payout}', [PayoutController::class, 'pay'])->name('payouts.pay');

        Route::get('/master-data', [LookupController::class, 'index'])->name('lookups.index');
        Route::post('/master-data/{type}', [LookupController::class, 'store'])
            ->whereIn('type', array_keys(LookupController::TYPES))->name('lookups.store');
        Route::patch('/master-data/{type}/{id}', [LookupController::class, 'toggle'])
            ->whereIn('type', array_keys(LookupController::TYPES))->whereNumber('id')->name('lookups.toggle');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::patch('/settings/{setting}', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');

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

        Route::post('/staff/{user}/invite', [
            StaffController::class,
            'resendInvite',
        ])
            ->name('staff.invite');
    });
