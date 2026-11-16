<?php

use App\Http\Controllers\Manager\EscalationController;
use App\Http\Controllers\Manager\ReportController;
use App\Http\Controllers\Manager\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:branch_manager'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('dashboard');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff/{user}/salary', [StaffController::class, 'salary'])->name('staff.salary');

        Route::get('/escalations', [EscalationController::class, 'index'])->name('escalations.index');
        Route::patch('/escalations/{escalation}', [EscalationController::class, 'resolve'])->name('escalations.resolve');
    });
